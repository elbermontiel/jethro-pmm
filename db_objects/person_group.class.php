<?php
include_once 'include/db_object.class.php';
class Person_Group extends db_object
{
	var $_save_permission_level = PERM_EDITGROUP;

	function _getFields()
	{
		return Array(
			'name'		=> Array(
									'type'		=> 'text',
									'width'		=> 40,
									'maxlength'	=> 128,
									'allow_empty'	=> FALSE,
									'initial_cap'	=> TRUE,
								   ),
			'categoryid'	=> Array(
									'type'	=> 'reference',
									'references' => 'person_group_category',
									'label' => 'Category',
									'allow_empty' => TRUE,
									'order_by' => 'name',
								),
			'is_archived'	=> Array(
									'type' => 'select',
									'options'	=> Array('Active', 'Archived'),
									'label' => 'Status',
									'default'	=> 0,
								),
			'can_record_attendance' => Array(
									'type'		=> 'select',
									'options'	=> Array('No', 'Yes'),
									),
			'share_member_details' => Array(
									'type' => 'select',
									'options' => Array('No', 'Yes'),
									'note' => 'If set to yes, members of this group will be able to see other members\' details when they log in to the <a href="'.BASE_URL.'members">member portal</a>'
								),
		);
	}

	function __construct($id=NULL) {
		parent::__construct($id);

		if (!$this->id) {
			$this->fields['is_archived']['editable'] = false;
		}
	
		if (!empty($_REQUEST['categoryid'])) {
			$_SESSION['group_categoryid'] = $_REQUEST['categoryid'];
		} else if (empty($this->id) && !empty($_SESSION['group_categoryid'])) {
			$this->values['categoryid'] = array_get($_SESSION, 'group_categoryid');
		}
	}

	function getInitSQL()
	{
		// Need to create the group-membership table as well as the group table
		return Array(
				parent::getInitSQL('_person_group'),

				"CREATE TABLE person_group_membership_status (
					id INT AUTO_INCREMENT PRIMARY KEY,
					label VARCHAR(255) NOT NULL,
					is_default TINYINT(1) UNSIGNED DEFAULT 0,
					CONSTRAINT UNIQUE INDEX (label)
				) ENGINE=InnoDB;",

				"INSERT INTO person_group_membership_status (label, is_default)
				VALUES ('Member', 1);",

				"CREATE TABLE `person_group_membership` (
				  `personid` int(11) NOT NULL default '0',
				  `groupid` int(11) NOT NULL default '0',
				  `membership_status` int DEFAULT NULL,
				  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
				  PRIMARY KEY  (`personid`,`groupid`),
				  INDEX personid (personid),
				  INDEX groupid (groupid),
				  CONSTRAINT `membership_status_fk` FOREIGN KEY (membership_status) REFERENCES person_group_membership_status (id) ON DELETE SET NULL
				) ENGINE=InnoDB",
		);
	}


	function toString()
	{
		return $this->values['name'];
	}

	function getMembers($incl_archived=TRUE, $order_by=NULL)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT p.*, gm.membership_status AS membership_status_id, ms.label as membership_status, gm.created as joined_group, c.name as congregation
				FROM person_group_membership gm 
				JOIN person p ON gm.personid = p.id
				';
		if ($order_by != NULL) {
			$sql .= '
				JOIN family f ON f.id = p.familyid
			';
		}
		$sql .= '
				LEFT JOIN congregation c ON c.id = p.congregationid
				LEFT JOIN person_group_membership_status ms ON ms.id = gm.membership_status
				WHERE gm.groupid = '.$db->quote((int)$this->id).'
				';
		if (!$incl_archived) {
			$sql .= ' AND p.status <> "archived"
					';
		}
		if ($order_by == NULL) {
			$order_by = 'p.last_name, p.first_name';
		} else {
			$order_by = preg_replace("/(^|[^.])status($| |,)/", '\\1p.status\\2', $order_by);
		}
		$sql .= 'ORDER BY '.$order_by;
		$res = $db->queryAll($sql, null, null, true);
		check_db_result($res);
		foreach ($res as $k => &$v) {
			$v['joined_group'] = format_date($v['joined_group']);
		}
		return $res;
	}
	
	function getMemberIDs($incl_archived=TRUE)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT p.id
				FROM person_group_membership gm 
				JOIN person p ON gm.personid = p.id
				LEFT JOIN congregation c ON c.id = p.congregationid
				LEFT JOIN person_group_membership_status ms ON ms.id = gm.membership_status
				WHERE gm.groupid = '.$db->quote((int)$this->id).'
				';
		if (!$incl_archived) {
			$sql .= ' AND p.status <> "archived"
					';
		}
		$sql .= 'ORDER BY p.last_name, p.first_name';
		$res = $db->queryCol($sql);
		check_db_result($res);
		return $res;		
	}

	function addMember($personid, $membership_status=NULL, $overwrite_existing=FALSE)
	{
		if (!$GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
			trigger_error("You do not have permission to add group members");
			return FALSE;
		}
		list($statuses, $default_status) = self::getMembershipStatusOptionsAndDefault();
		if ($membership_status === NULL) $membership_status = $default_status;
		if (!isset($statuses[$membership_status])) {
			trigger_error("Invalid membership status value '$membership_status'", E_USER_ERROR);
			return FALSE;
		}

		$new_member = $GLOBALS['system']->getDBObject('person', $personid);
		if ($new_member->id) {
			$db =& $GLOBALS['db'];
			if ($overwrite_existing) {
				$sql = 'INSERT ';
			} else {
				$sql = 'INSERT IGNORE ';
			}
			$sql .= 'INTO person_group_membership (groupid, personid, membership_status)
					VALUES ('.$db->quote((int)$this->id).', '.$db->quote((int)$personid).', '.$db->quote($membership_status).')';
			if ($overwrite_existing) {
				$sql .= ' ON DUPLICATE KEY UPDATE membership_status=VALUES(membership_status)';
			}
			$res = $db->query($sql);
			check_db_result($res);
			return TRUE;
		}
		return FALSE;
	}

	function removeMember($personid)
	{
		if (!$GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
			trigger_error("You do not have permission to remove group members");
			return FALSE;
		}
		$new_member = $GLOBALS['system']->getDBObject('person', $personid);
		if ($new_member->id) {
			$db =& $GLOBALS['db'];
			$sql = 'DELETE FROM person_group_membership
					WHERE groupid = '.$db->quote((int)$this->id).'
						AND personid = '.$db->quote((int)$personid);
			$res = $db->query($sql);
			check_db_result($res);
			return TRUE;
		}
		return FALSE;
	}

	function removeMembers($personids) 
	{
		if (!$GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
			trigger_error("You do not have permission to remove group members");
			return FALSE;
		}
		// Do a query first to make sure it's only persons we have access to.
		$members = $GLOBALS['system']->getDBObjectData('person', Array('id' => $personids));
		if ($members) {
			$db =& $GLOBALS['db'];
			$SQL = 'DELETE FROM person_group_membership 
					WHERE groupid = '.$db->quote((int)$this->id).'
						AND personid IN ('.implode(',', array_map(Array($db, 'quote'), array_keys($members))).')';
			$res = $db->query($SQL);
			check_db_result($res);
			return TRUE;
		}
		return FALSE;
	}


	static function getGroups($personid, $includeArchived=FALSE, $whichShareMemberDetails=NULL)
	{
		$db =& $GLOBALS['db'];
		$sql = 'SELECT g.id, g.name, gm.created, g.is_archived, g.categoryid, pgms.label as membership_status
				FROM person_group_membership gm 
				JOIN person_group g ON gm.groupid = g.id
				LEFT JOIN person_group_membership_status pgms ON pgms.id = gm.membership_status
				WHERE gm.personid = '.$db->quote((int)$personid).'
				'.($includeArchived ? '' : ' AND NOT g.is_archived').'
				'.(is_null($whichShareMemberDetails) ? '' : ' AND g.share_member_details = '.(int)$whichShareMemberDetails).' 
				ORDER BY g.name';
		$res = $db->queryAll($sql, null, null, true);
		check_db_result($res);
		return $res;
	}

	function printSummary() 
	{
		?>
		<table class="standard">
			<tr>
				<th>Group Name</th>
				<td><?php echo $this->getValue('name'); ?></td>
			</tr>
			<tr>
				<th>Members</th>
				<td>
					<ul>
					<?php
					foreach ($this->getMembers() as $id => $details) {
						?>
						<li><a href="?view=persons&personid=<?php echo $id; ?>"><?php echo $details['first_name'].' '.$details['last_name']; ?></a></li>
						<?php
					}
					?>
					</ul>
				</td>
			</tr>
		</table>
		<?php
	}

		
	function getInstancesQueryComps($params, $logic, $order)
	{
		$res = parent::getInstancesQueryComps($params, $logic, $order);
		$res['from'] = '('.$res['from'].') LEFT OUTER JOIN person_group_membership gm ON gm.groupid = person_group.id';
		$res['select'][] = 'COUNT(gm.personid) as member_count';
		$res['group_by'] = 'person_group.id';
		return $res;

	}

	function delete()
	{
		parent::delete();
		$db =& $GLOBALS['db'];
		$sql = 'DELETE FROM person_group_membership WHERE groupid = '.$db->quote($this->id);
		$res = $db->query($sql);
		check_db_result($res);
	}

	function printFieldValue($fieldname, $value=NULL)
	{
		if (is_null($value)) $value = $this->values[$fieldname];
		switch ($fieldname) {
			case 'categoryid':
				if ($value == 0) {
					echo '<i>(Uncategorised)</i>';
					return;
				}
				// deliberate fall through
			default:
				return parent::printFieldValue($fieldname, $value);
		}
	}

	function printFieldInterface($fieldname, $prefix='')
	{
		if ($fieldname == 'categoryid') {
			$GLOBALS['system']->includeDBClass('person_group_category');
			Person_Group_Category::printChooser($prefix.$fieldname, $this->getValue('categoryid'));
			echo ' &nbsp; &nbsp;<small><a href="'.build_url(Array('view' => 'groups__manage_categories')).'">Manage categories</a></small>';
		} else {
			return parent::printFieldInterface($fieldname, $prefix);
		}
	}

	public static function getMembershipStatusOptionsAndDefault()
	{
		$sql = 'SELECT * FROM person_group_membership_status ORDER BY label';
		$res = $GLOBALS['db']->queryAll($sql, null, null, true);
		check_db_result($res);
		$options = Array();
		$default = null;
		foreach ($res as $id => $detail) {
			$options[$id] = $detail['label'];
			if ($detail['is_default']) $default = $id;
		}
		if (empty($default)) $default = key($options);
		return Array($options, $default);
	}
		

	public static function printMembershipStatusChooser($name, $value=NULL, $multi=FALSE)
	{
		list($options, $default) = self::getMembershipStatusOptionsAndDefault();
		$params = Array(
			'type' => 'select',
			'options' => $options,
			'class' => 'autofocus',
		);
		if (empty($value)) $value = $default;
		if ($multi) {
			$params['allow_multiple'] = true;
			if (substr($name, -2) != '[]') $name .= '[]';
		}
		print_widget($name, $params, $value);
	}


	public function updateMembershipStatuses($vals)
	{
		$GLOBALS['system']->doTransaction('BEGIN');
		list($options, $default) = self::getMembershipStatusOptionsAndDefault();
		foreach ($vals as $personid => $status) {
			if (!isset($options[$status])) {
				trigger_error("Invalid person status $status not saved");
				continue;
			}
			$res = $GLOBALS['db']->query('UPDATE person_group_membership
										SET membership_status = '.$GLOBALS['db']->quote($status).'
										WHERE groupid = '.(int)$this->id.'
											AND personid = '.(int)$personid);
			check_db_result($res);
		}
		$GLOBALS['system']->doTransaction('COMMIT');
		return TRUE;
	}

	static function printMultiChooser($name, $value, $exclude_groups=Array(), $allow_category_select=FALSE)
	{
		?>
		<table class="expandable">
		<?php
		foreach ($value as $id) {
			?>
			<tr>
				<td>
					<?php Person_Group::printChooser($name.'[]', $id, $exclude_groups, $allow_category_select); ?>
				</td>
			</tr>
			<?php
		}
		?>
			<tr>
				<td>
					<?php Person_Group::printChooser($name.'[]', 0, $exclude_groups, $allow_category_select); ?>
				</td>
			</tr>
		</table>
		<?php
	}


	static function printChooser($fieldname, $value, $exclude_groups=Array(), $allow_category_select=
	FALSE, $empty_text='(Choose)')
	{
		$cats = $GLOBALS['system']->getDBObjectData('person_group_category', Array(), 'OR', 'name');
		$groups = $GLOBALS['system']->getDBObjectData('person_group', Array('is_archived' => 0), 'OR', 'name');
		if (empty($groups)) {
			?><i>There are no groups in the system yet</i><?php
			return FALSE;
		}
		?>
		<select name="<?php echo $fieldname; ?>">
			<option value=""><?php echo ents($empty_text); ?></option>
			<?php
			self::_printChooserOptions($cats, $groups, $value, $allow_category_select);
			if ($allow_category_select) {
				$sel = ($value === 'c0') ? ' selected="selected"' : '';
				?>
				<option value="c0" class="strong"<?php echo $sel; ?>>Uncategorised Groups (ALL)</option>
				<?php 
				self::_printChooserGroupOptions($groups, 0, $value);
			} else {
				?>
				<optgroup label="Uncategorised Groups">
				<?php self::_printChooserGroupOptions($groups, 0, $value); ?>
				</optgroup>
				<?php
			}
			?>
		</select>
		<?php
		
		return TRUE;
	}

	function _printChooserOptions($cats, $groups, $value, $allow_category_select=FALSE, $parentcatid=0, $prefix='')
	{
		foreach ($cats as $cid => $cat) {
			if ($cat['parent_category'] != $parentcatid) continue;
			if ($allow_category_select) {
				$sel = ($value === 'c'.$cid) ? ' selected="selected"' : '';
				?>
				<option value="c<?php echo $cid; ?>" class="strong"<?php echo $sel; ?>><?php echo $prefix.ents($cat['name']); ?> (ALL)</option>
				<?php
				self::_printChooserGroupOptions($groups, $cid, $value, $prefix.'&nbsp;&nbsp;&nbsp;');
				self::_printChooserOptions($cats, $groups, $value, $allow_category_select, $cid, $prefix.'&nbsp;&nbsp;');
			} else {
				?>
				<optgroup label="<?php echo $prefix.ents($cat['name']); ?>">
				<?php
				self::_printChooserGroupOptions($groups, $cid, $value);
				self::_printChooserOptions($cats, $groups, $value, $allow_category_select, $cid, $prefix.'&nbsp;&nbsp;');
				?>
				</optgroup>
				<?php
			}
		}
	}

	function _printChooserGroupOptions($groups, $catid, $value, $prefix='')
	{
		foreach ($groups as $gid => $group) {
			if ($group['categoryid'] != $catid) continue;
			$sel = ($gid == $value) ? ' selected="selected"' : '';
			?>
			<option value="<?php echo (int)$gid; ?>"<?php echo $sel; ?>><?php echo $prefix.ents($group['name']); ?></option>
			<?php
		}
	}





}

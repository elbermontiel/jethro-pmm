<?php
/**
 * JETHRO PMM
 * 
 * conf.php - edit this file to configure jethro
 *
 * @author Tom Barrett <tom@tombarrett.id.au>
 * @version $Id: conf.php.sample.au,v 1.20 2013/07/31 11:35:05 tbar0970 Exp $
 * @package jethro-pmm
 */

define('MEMBER_DIRECTORY_GROUPID', 2);
define('MEMBER_DIRECTORY_USERNAME', 'foo');
define('MEMBER_DIRECTORY_PASSWORD', 'bar');
define('MEMBER_DIRECTORY_HEADER', '
<style>
                                body {
                                        margin: 25px;
                                }
                                * {
                                        font-family: sans-serif;
                                }
                                td {
                                        padding: 3px 20px 3px 0px;
                                        font-size: 90%;
                                }
                                h2 {
                                        font-size: 120%;
                                        border-bottom: 1px solid #ccc;
                                        margin-top: 2ex;
                                }
                                h3 {
                                        margin: 0px;
                                        font-size: 100%;
                                        font-weight: normal;
                                }
                                </style>
<h1>Member Directory</h1>
');

///////////////////////////////////////////////////////////////////////////
// ESSENTIAL SETTINGS - these must be correct for the system to run at all:
///////////////////////////////////////////////////////////////////////////

// Name of your system - shows at the top of every page
define('SYSTEM_NAME', "RELEASE TEST");

// Database details - you need to replace at least USERNAME, PASSWORD and DB_NAME for both of these.
// The Private DSN is used for the main system that users log in to.
// For enhanced security you can use a different database user for the Public DSN
// and only grant reduced access privileges to that user (eg only SELECT priveleges, only certain tables)
// Ref: http://en.wikipedia.org/wiki/Database_Source_Name
define('PUBLIC_DSN', "mysql://root:@localhost/jethro");
define('PRIVATE_DSN', "mysql://root:@localhost/jethro");

// The URL jethro will be running at.  NB The final slash is important!!
define('BASE_URL', 'http://192.168.1.10/jethro-releasetest/');

// Whether the system must be accessed by HTTPS.
// If this is true, the BASE_URL above must begin with https://
define('REQUIRE_HTTPS', FALSE);

////////////////////////////////////////////////////////////
// DATA STRUCTURE SETTINGS
////////////////////////////////////////////////////////////

// The options for person status - NB the system-defined options "Contact" and "Archived" will be added
define('PERSON_STATUS_OPTIONS', 'Core,Crowd');
define('PERSON_STATUS_DEFAULT', 'Contact');

// The options for age bracket
// NOTE 1: The first one must be "Adult"
// NOTE 2: if you change the number of options here AFTER installing, you will need to manually
// update your database to fix the existing entries, so it's wise to get it right from the start
define('AGE_BRACKET_OPTIONS', 'Adult,High School,Upper Primary,Lower Primary,Infants School,Toddler,Baby');

// The place where the "documents" view will store files.
// If blank, defaults to [yourJethroRoot]/files
define('DOCUMENTS_ROOT_PATH', '');

// To use the "generate service documents" feature, add folder paths here.
// They can be absolute paths, or relative within the documents root above.
// Separate multiple entries with pipe (|).  
define('SERVICE_DOCS_TO_POPULATE_DIRS', 'Templates/To_Populate');
define('SERVICE_DOCS_TO_EXPAND_DIRS', 'Templates/To_Expand');


////////////////////////////////////////////////////////////
// JETHRO BEHAVIOUR OPTIONS
////////////////////////////////////////////////////////////

// The Jethro features that are enabled
// You can remove features from here to hide them in your system
// Options: NOTES,PHOTOS,DATES,ATTENDANCE,ROSTERS&SERVICES,SERVICEDETAILS,DOCUMENTS,SERVICEDOCUMENTS
define('ENABLED_FEATURES', 'NOTES,PHOTOS,DATES,ATTENDANCE,ROSTERS&SERVICES,SERVICEDETAILS,DOCUMENTS,SERVICEDOCUMENTS');

// The default permission level for new accounts.
// To find the value for this, edit a user with the permissions you want and look at the grey number next to the permissions field
define('DEFAULT_PERMISSIONS', 7995391);

// Whether a note is compulsory when adding a new family
define('REQUIRE_INITIAL_NOTE', true); 

// The order in which persons are listed when marking or reporting on 
// The default is by status (core first) then last name, age bracket (adults first), gender (male first)
define('ATTENDANCE_LIST_ORDER', 'status ASC, family_name ASC, last_name ASC, age_bracket ASC, gender DESC');

// Number of weeks ahead to show in rosters by default
define('ROSTER_WEEKS_DEFAULT', 8);

// How many columns should a roster be to have the date repeated on the right hand side?
define('REPEAT_DATE_THRESHOLD', 10);

// If you want Jethro to use a different timezone to the server it runs on,
// enter one here.  See  php.net/manual/en/timezones.php for valid timezones.
define('TIMEZONE', 'Australia/Sydney');

// Security setting: Require a user to log in again if they haven't done anything for this length of time
define('SESSION_TIMEOUT_MINS', 90);

// Security setting: Require a user to log in again if their last login was more than this long ago
define('SESSION_MAXLENGTH_MINS', 60*8);

// How to contact the system administrator (probably the person editing this file)
// either mailto:someone@domain.com or http://somedomain.com/info-page
define('SYSADMIN_HREF', '');

// Where to email errors to
define('ERRORS_EMAIL_ADDRESS', '');


////////////////////////////////////////////////////////////////
// EXTERNAL TOOLS SETTINGS - how Jethro should talk to other services
////////////////////////////////////////////////////////////////

// URL for bible passage links - NB needs to include __REFERENCE__ keyword
define('BIBLE_URL', 'http://www.biblestudytools.com/nrs/passage.aspx?q=__REFERENCE__');

// The maximum number of email addresses to send to at once.
// Depends on the SMTP mail server your users are using.
define('EMAIL_CHUNK_SIZE', 25);

// SMS GATEWAY:
// ------------

// URL of the server to send SMSes through
define('SMS_HTTP_URL', ''); // eg http://www.5centsms.com.au/api/send.php

// The format for the send-sms POST request
// Can contain keywords _USER_MOBILE_ _USER_EMAIL_ _MESSAGE_ _RECIPIENTS_COMMAS_ _RECIPIENTS_NEWLINES_
// eg 'username=abc&password=xyz&to=_RECIPIENTS_COMMAS_&sender=_USER_MOBILE_&message=_MESSAGE_');
define('SMS_HTTP_POST_TEMPLATE', ''); 

// Regex to use to detect success message from the SMS server for each recipient
// _RECIPIENT_ keyword is available.  Leave blank to ignore response.
// eg ^1\|_RECIPIENT_\|[0-9]+\|OK<br>
define('SMS_HTTP_RESPONSE_OK_REGEX', '');

// A file to log who has sent SMSes (optional)
define('SMS_SEND_LOGFILE', '');

////////////////////////////////////////////////////////////////////////
// LOCALE-SPECIFIC SETTINGS you may need to change if outside Australia:
////////////////////////////////////////////////////////////////////////

define('ENVELOPE_WIDTH_MM', 220);
define('ENVELOPE_HEIGHT_MM', 110);

define('ADDRESS_STATE_OPTIONS', 'ACT,NSW,NT,QLD,SA,TAS,VIC,WA');
define('ADDRESS_STATE_DEFAULT', 'NSW');
define('ADDRESS_STATE_LABEL', 'State');

define('ADDRESS_POSTCODE_LABEL', 'Postcode');
define('ADDRESS_POSTCODE_WIDTH', 4);
define('ADDRESS_POSTCODE_REGEX', '/^[0-9][0-9][0-9][0-9]$/');

define('HOME_TEL_FORMATS', 'XXXX-XXXX
(XX) XXXX-XXXX');
define('WORK_TEL_FORMATS', 'XXXX-XXXX
(XX) XXXX-XXXX');
define('MOBILE_TEL_FORMATS', 'XXXX XXX XXX');

define('POSTCODE_LOOKUP_URL','http://www1.auspost.com.au/postcodes/index.asp?Locality=__SUBURB__&sub=1&State=&Postcode=&submit1=Search');
define('MAP_LOOKUP_URL', 'http://maps.google.com.au?q=__ADDRESS_STREET__,%20__ADDRESS_SUBURB__,%20__ADDRESS_STATE__,%20__ADDRESS_POSTCODE__');


///////////////////////////////////////////////////////
// TECHNICAL SETTINGS you will not likely bother with:
///////////////////////////////////////////////////////
define('LOCK_LENGTH', '10 minutes');
define('LOCK_CLEANUP_PROBABLILITY', 10);

// The maximum SMS length users are allowed to send.  160 chars is usually a one-part SMS.
define('SMS_MAX_LENGTH', 160);

// the chunk size to aim for when dividing lists (of persons or families) into pages
define('CHUNK_SIZE', 100);

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'tool_uploadactivitycompletions', language 'en'
 *
 * @package    tool_uploadactivitycompletions
 * @copyright  2020 Tim St.Clair (https://github.com/frumbert/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Upload activity completions';
$string['importfile'] = 'CSV file';

$string['invalidimportfile'] = 'File format is invalid.';
$string['import'] = 'Import';
$string['completionsprocessed'] = 'Completions processed successfully';

$string['cachedef_helper'] = 'Upload page caching';

$string['confirm'] = 'Confirm';
$string['confirmcolumnmappings'] = 'Confirm the columns mappings';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvdelimiter_help'] = 'CSV delimiter of the CSV file.';
$string['encoding'] = 'Encoding';
$string['encoding_help'] = 'Encoding of the CSV file.';
$string['columnsheader'] = 'Columns';

// Tracker.
$string['csvline'] = 'Line';
$string['id'] = 'ID';
$string['result'] = 'Result';
$string['uploadactivitycompletionsresult'] = 'Upload results';
$string['completionstotal'] = 'Completions total: {$a}';
$string['completionsadded'] = 'Completions added: {$a}';
$string['completionsskipped'] = 'Completions skipped: {$a}';
$string['completionserrors'] = 'Completions errors: {$a}';

// CLI.
$string['invalidcsvfile'] = 'File format is invalid.';
$string['invalidencoding'] = 'Invalid encoding specified';

// Helper.
$string['tool_intro'] = "
<p>Use this tool to import manual user completions against activities within courses. Users will be manually enrolled as a student if required. Use a standard CSV file that contains the courses, users and activities to import completions against.</p>
<ul>
<li>You need to specify the course (short) name or the course idnumber. The course needs to have completion enabled.</li>
<li>You need to specify the user name or idnumber.</li>
<li>You need to specify the section (topic) name that contains the activity to complete (case insensitive). If your activity is in the top section, write '0' as your topic name.</li>
<li>You need to specify the name of the activity to complete (e.g. the page, quiz, scorm, url, etc). This activity needs to have completion enabled.</li>
</ul>
<p>The csv column names are [<a download href='/admin/tool/uploadactivitycompletions/example.csv'>Sample</a>]:</p>
<p><code>coursename, courseidnumber, username, useridnumber, sectionname, activityname</code></p>
<p>If you specify both coursename/idnumber or username/idnumber it the idnumber will take precedence.</p>
";


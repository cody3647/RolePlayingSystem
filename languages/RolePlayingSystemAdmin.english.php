<?php
// Version: 1.0; Role Playing System Administration

//Menu
$txt['rps_game'] = 'Game';
$txt['rps_create'] = 'Create Character';
$txt['rps_tags'] = 'Tags List';
$txt['rps_characters'] = 'Character List';
$txt['rps_gamecalendar'] = 'In-game Calendar';

//Manage Boards
$txt['rps_ic_board'] = 'In Character Board:';
$txt['rps_ic_board_desc'] = 'Makes a board an in character board.  Only Characters can post here after it is changed.  If it is turned off, character posts will show up as being from the ooc/player account.';

//Errors
$txt['error_no_character_ic'] = 'Character selection required for an In Character board.';
$txt['error_not_a_character'] = 'The character you are looking for does not exist.';

//Admin Menu
$txt['rps_title'] = 'Role Playing System';
$txt['core_settings_item_rps'] = 'Role Playing System';
$txt['core_settings_item_rps_desc'] = 'This enables the Role Playing System Mod.';

$txt['rps_manage'] = 'Manage Role Playing System';
$txt['rps_settings'] = 'General Settings';
$txt['rps_settings_desc'] = 'From this page you can set the Role Playing Date Range, the first major date of the game, and other settings';
$txt['rps_manage_characters'] = 'Approve Characters';
$txt['rps_manage_characters_desc'] = 'From this page you can approve new characters.';
$txt['rps_manage_bios'] = 'Approve Biographies';
$txt['rps_manage_bios_desc'] = 'From this page you can approve new or changed character biographies.';
$txt['rps_approve_bios'] = 'Approve Biographies';
$txt['rps_approve_bios_desc'] = 'From this page you can approve new and modified biographies.';
$txt['rps_manage_tags'] = 'Manage Tags';
$txt['rps_manage_tags_desc'] = 'From this page you can edit and remove tags from the entire forum.  <br /><br />If a tag is changed to be the same as another, the tags will be merged.  They cannot be seperated.';
$txt['rps_events'] = 'Events';
$txt['rps_events_desc'] = 'From this page you can manage events for the game calendar';
$txt['rps_phases'] = 'Moon Phases';
$txt['rps_phases_desc'] = 'From this page you can manage moon phases for the game calendar';
$txt['rps_download'] = 'Download Holidays and Moon Phases';
$txt['rps_download_desc'] = 'Download Christian, Jewish, and  Islamic holidays with changing dates, as well as moon phases.  Information originally gathered from <a href="http://aa.usno.navy.mil/data/docs/api.php" title="U.S. Naval Observatory Astronomical Applications API v2.0">U.S. Naval Observatory Astronomical Applications API v2.0</a>.';

//Permissions
$txt['permissiongroup_rps'] = 'Role Playing System';
$txt['permissionname_rps_char_view'] = 'View character summary and stats';
$txt['permissionhelp_rps_char_view'] = 'This permission allows users clicking on a user name to see a summary of profile settings, some statistics and all posts of the character.';
$txt['permissionname_rps_char_edit'] = 'Edit Character Profiles';
$txt['permissionhelp_rps_char_edit'] = 'The ability to edit the information in a character profile.';
$txt['permissionname_rps_char_edit_own'] = 'Own character';
$txt['permissionname_rps_char_edit_any'] = 'Any character';
$txt['permissionname_rps_char_title'] = 'Edit custom title of character';
$txt['permissionhelp_rps_char_title'] = 'The custom title is shown on the topic display page, under the profile of each character that has a custom title.';
$txt['permissionname_rps_char_title_own'] = 'Own character';
$txt['permissionname_rps_char_title_any'] = 'Any character';
$txt['permissionname_rps_create_char'] = 'Create new character';
$txt['permissionhelp_rps_create_char'] = 'The ability to create a new character.  Does not affect current characters of the member.';
$txt['permissionname_rps_add_tags'] = 'Add tags to topics';
$txt['permissionhelp_rps_add_tags'] = 'This permission allows a member to add tags to a topic, either their own topics or all topics.';
$txt['permissionname_rps_add_tags_own'] = 'Own topics';
$txt['permissionname_rps_add_tags_any'] = 'Any topics';
$txt['permissionname_rps_remove_tags'] = 'Remove tags from topics';
$txt['permissionhelp_rps_remove_tags'] = 'Allows a member to delete tags from topics.  If a tag is deleted from a topic and not present on any other topic it will be deleted completely.';
$txt['permissionname_rps_remove_tags_own'] = 'Own topics';
$txt['permissionname_rps_remove_tags_any'] = 'Any account';
$txt['permissionname_rps_char_set_avatar'] = 'Select a character avatar';
$txt['permissionname_rps_bio_approved'] = 'Automatically approve character biography changes.';

//Settings
$txt['rps_general_settings'] = 'General Settings';

$txt['setting_rps_begining'] = 'First In-Game Date';
$txt['rps_beginning_desc'] = 'This is the first in-game date.  Posts can be set before this, but the calendar will not browse before this date.';
$txt['setting_rps_current_date_range'] = 'Current Date Range';
$txt['rps_dates_message'] = 'There should be no more than four(4) months between the Start and End Current Dates.';
$txt['setting_rps_current_start'] = 'Start';
$txt['setting_rps_current_end'] = 'End';
$txt['rps_date_format'] = '(YYYY-MM-DD)';

$txt['rps_error_incorrect_format'] = '<span class="error">%1$s is not in YYYY-MM-DD format.</span>';
$txt['rps_error_begining_date_later_start'] = '<span class="error">The Start Date, %1$s, is earlier than the Beginning Date, %2$s.</span>';
$txt['rps_error_start_date_later_end'] = '<span class="error">The Start Date, %1$s, is after the End Date, %2$s.</span>';
$txt['rps_error_large_range'] = '<span class="error">The range, %3$s - %4$s, is %1$d month(s) and %2$d day(s) greater than 4 months.</span>';

$txt['rps_biography_settings'] = 'Biography Edit Settings';
$txt['rps_bio_edit_count'] = 'Minor Edits Before Re-approval';
$txt['rps_bio_edit_count_desc'] = 'The number of minor edits a member can make before the biography needs re-approval.';
$txt['rps_bio_edit_count_after'] = '(0 for all edits need approval)';
$txt['rps_bio_edit_chars'] = 'Maximum Character Count of a Minor Edit';
$txt['rps_bio_edit_chars_desc'] = 'This is the number of characters that can be changed while still counting as a minor edit.';
$txt['rps_bio_edit_chars_after'] = '(0 for no maximum.  All changes are considered a small edit.)';


$txt['rps_calendar_settings'] = 'Game Calendar Settings';
$txt['setting_rps_showholidays'] = 'Show Holidays';
$txt['setting_rps_showbdays'] = 'Show Birthdays';
$txt['setting_rps_showtopics'] = 'Show Topics';
$txt['setting_cal_show_never'] = 'Never';
$txt['setting_cal_show_cal'] = 'In calendar only';
$txt['setting_cal_show_index'] = 'On board index only';
$txt['setting_cal_show_all'] = 'On board index and calendar';

//Manage Game Calendar
$txt['first'] = 'First';
$txt['second'] = 'Second';
$txt['third'] = 'Third';
$txt['fourth'] = 'Fourth';
$txt['last'] = 'Last';
$txt['of'] = 'of';
$txt['exact_day'] = 'Exact Day';
$txt['relative_day'] = 'Relative Day';
$txt['rps_events_list'] = 'Current Game Calendar Events';
$txt['rps_events_none'] = 'You have not created any events for the Game Calendar yet.';
$txt['rps_header_event'] = 'Events/Holidays';
$txt['rps_events_delete_confirm'] = 'Are you sure you wish to remove these events?';
$txt['rps_manage_events'] = '';
$txt['rps_add_event'] = 'Add Event';
$txt['rps_edit_event'] = 'Edit Event';
$txt['rps_Christian'] = 'Christian Holidays';
$txt['rps_Christian_desc'] = 'Data will be provided for the years 1583 C.E. through 2100 C.E.';
$txt['rps_Jewish'] = 'Jewish Holidays';
$txt['rps_Jewish_desc'] = 'Data will be provided for the years 622 C.E. (A.M. 4382) through 2100 C.E. (A.M. 5862).';
$txt['rps_Islamic'] = 'Islamic Holidays';
$txt['rps_Islamic_desc'] = 'Data will be provided for the years 622 C.E. (A.H. 1) through 2100 C.E. (A.H. 1767).';
$txt['rps_moon_phases'] = 'Moon Phases';
$txt['rps_moon_phases_desc'] = ' The data can be generated for any year between 1700 C.E. and 2100 C.E.';
$txt['rps_moon_phases_list_none'] = 'No moon phases have been added, yet.';
$txt['rps_timezone'] = 'Timezone for Game';
$txt['rps_phases_list'] = 'List of Moon Phases';
$txt['rps_phases_list_phase'] = 'Phase';
$txt['rps_phase_title_add'] = 'Add Moon Phase';
$txt['rps_phase_title_edit'] = 'Edit Moon Phase';
$txt['rps_phase_label_phase'] = 'Phase: ';
$txt['rps_phase_label_time'] = 'Time: ';
$txt['rps_phases_list_none'] = 'There are currently no moon phases to display.';

//Tags
$txt['rps_tags_'] = '';
$txt['rps_tags_'] = '';
$txt['rps_tags_'] = '';
$txt['rps_tags_'] = '';

//ManageCharacters
$txt['rps_manage_characters'] = 'Manage Characters';
$txt['rps_characters_unapproved_list_none'] = 'There are no unapproved characters right now.';
$txt['rps_approve_characters'] = 'Approve Characters';
$txt['rps_characters_list_approve'] = 'Approve';
$txt['rps_characters_list_character'] = 'Character';
$txt['rps_characters_list_member'] = 'Member';

//ManageBios
$txt['rps_manage_bio'] = 'Manage Biographies';
$txt['rps_bios_unapproved_list_none'] = 'There are no unapproved biographies right now.';
$txt['rps_bios_list_character'] = 'Character';
$txt['rps_bios_list_member'] = 'Member';
$txt['rps_bios_list_date'] = 'Date Added/Last Modified';
$txt['rps_bios_list_approve'] = 'Approve';
$txt['rps_approve_bios'] = 'Approve Biographies';
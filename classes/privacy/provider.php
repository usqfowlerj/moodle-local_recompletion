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
 * local_recompletion Data provider.
 *
 * @package    local_recompletion
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2018 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recompletion\privacy;
defined('MOODLE_INTERNAL') || die();

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{writer, transform, helper, contextlist, approved_contextlist};
use stdClass;

/**
 * Data provider for local_recompletion.
 *
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2018 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\metadata\provider
{

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table('local_recompletion_cc', [
            'userid' => 'privacy:metadata:userid',
            'course' => 'privacy:metadata:course',
            'timeenrolled' => 'privacy:metadata:timeenrolled',
            'timestarted' => 'privacy:metadata:timestarted',
            'timecompleted' => 'privacy:metadata:timecompleted',
            'reaggregate' => 'privacy:metadata:reaggregate'
        ], 'privacy:metadata:local_recompletion_cc');

        $collection->add_database_table('local_recompletion_cmc', [
            'userid' => 'privacy:metadata:userid',
            'coursemoduleid' => 'privacy:metadata:coursemoduleid',
            'completionstate' => 'privacy:metadata:completionstate',
            'viewed' => 'privacy:metadata:viewed',
            'overrideby' => 'privacy:metadata:overrideby',
            'timemodified' => 'privacy:metadata:timemodified'
        ], 'privacy:metadata:local_recompletion_cmc');

        $collection->add_database_table('local_recompletion_cc_cc', [
            'userid' => 'privacy:metadata:userid',
            'course' => 'privacy:metadata:course',
            'gradefinal' => 'privacy:metadata:gradefinal',
            'unenroled' => 'privacy:metadata:unenroled',
            'timecompleted' => 'privacy:metadata:timecompleted'
        ], 'privacy:metadata:local_recompletion_cc_cc');

        $collection->add_database_table('local_recompletion_qa', [
            'attempt'               => 'privacy:metadata:quiz_attempts:attempt',
            'currentpage'           => 'privacy:metadata:quiz_attempts:currentpage',
            'preview'               => 'privacy:metadata:quiz_attempts:preview',
            'state'                 => 'privacy:metadata:quiz_attempts:state',
            'timestart'             => 'privacy:metadata:quiz_attempts:timestart',
            'timefinish'            => 'privacy:metadata:quiz_attempts:timefinish',
            'timemodified'          => 'privacy:metadata:quiz_attempts:timemodified',
            'timemodifiedoffline'   => 'privacy:metadata:quiz_attempts:timemodifiedoffline',
            'timecheckstate'        => 'privacy:metadata:quiz_attempts:timecheckstate',
            'sumgrades'             => 'privacy:metadata:quiz_attempts:sumgrades',
        ], 'privacy:metadata:quiz_attempts');

        $collection->add_database_table('local_recompletion_qg', [
            'quiz'                  => 'privacy:metadata:quiz_grades:quiz',
            'userid'                => 'privacy:metadata:quiz_grades:userid',
            'grade'                 => 'privacy:metadata:quiz_grades:grade',
            'timemodified'          => 'privacy:metadata:quiz_grades:timemodified',
        ], 'privacy:metadata:quiz_grades');

        $collection->add_database_table('local_recompletion_sst', [
            'userid' => 'privacy:metadata:userid',
            'attempt' => 'privacy:metadata:attempt',
            'element' => 'privacy:metadata:scoes_track:element',
            'value' => 'privacy:metadata:scoes_track:value',
            'timemodified' => 'privacy:metadata:timemodified'
        ], 'privacy:metadata:scorm_scoes_track');

        return $collection;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                return;
            }
            $params = array('userid' => $userid, 'course' => $context->instanceid);
            $records = $DB->get_records('local_recompletion_cc', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'course_completion'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }

            $records = $DB->get_records('local_recompletion_cc_cc', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'course_completion_criteria_compl'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }

            $records = $DB->get_records('local_recompletion_cmc', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'course_module_completion'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }

            $records = $DB->get_records('local_recompletion_qa', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'quiz_attempts'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }

            $records = $DB->get_records('local_recompletion_qg', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'quiz_grades'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }

            $records = $DB->get_records('local_recompletion_sst', $params);
            foreach ($records as $record) {
                $context = \context_course::instance($record->course);
                writer::with_context($context)->export_data(
                    [get_string('recompletion', 'local_recompletion'), 'scorm_tracks'],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $records)]);
            }
        }
    }

    /**
     * Helper function to transform a row from the database in to session data to export.
     *
     * The properties of the "dbrow" are very specific to the result of the SQL from
     * the export_user_data function.
     *
     * @param stdClass $dbrow A row from the database containing session information.
     * @return stdClass The transformed row.
     */
    private static function transform_db_row_to_session_data(stdClass $dbrow) : stdClass {
        if (isset($dbrow->timeenrolled) && (!empty($dbrow->timeenrolled))) {
            $dbrow->timeenrolled =  transform::datetime($dbrow->timeenrolled);
        }

        if (isset($dbrow->timestarted) && (!empty($dbrow->timestarted))) {
            $dbrow->timestarted =  transform::datetime($dbrow->timestarted);
        }

        if (isset($dbrow->timecompleted) && (!empty($dbrow->timecompleted))) {
            $dbrow->timecompleted =  transform::datetime($dbrow->timecompleted);
        }

        if (isset($dbrow->timemodified) && (!empty($dbrow->timemodified))) {
            $dbrow->timemodified =  transform::datetime($dbrow->timemodified);
        }

        if (isset($dbrow->timemodifiedoffline) && (!empty($dbrow->timemodifiedoffline))) {
            $dbrow->timemodifiedoffline =  transform::datetime($dbrow->timemodifiedoffline);
        }

        if (isset($dbrow->timestart) && (!empty($dbrow->timestart))) {
            $dbrow->timestart =  transform::datetime($dbrow->timestart);
        }

        if (isset($dbrow->timefinish) && (!empty($dbrow->timefinish))) {
            $dbrow->timefinish =  transform::datetime($dbrow->timefinish);
        }

        return $dbrow;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }
        $courseid = $context->instanceid;

        $params = array('course' => $courseid);
        $DB->delete_records('local_recompletion_cc', $params);
        $DB->delete_records('local_recompletion_cc_cc', $params);
        $DB->delete_records('local_recompletion_cmc', $params);
        $DB->delete_records('local_recompletion_qa', $params);
        $DB->delete_records('local_recompletion_qg', $params);
        $DB->delete_records('local_recompletion_sst', $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $courseid = $context->instanceid;
            $params = array('userid' => $userid, 'course' => $courseid);
            $DB->delete_records('local_recompletion_cc', $params);
            $DB->delete_records('local_recompletion_cc_cc', $params);
            $DB->delete_records('local_recompletion_cmc', $params);
            $DB->delete_records('local_recompletion_qa', $params);
            $DB->delete_records('local_recompletion_qg', $params);
            $DB->delete_records('local_recompletion_sst', $params);
        }
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $params = array('contextlevel' => CONTEXT_COURSE, 'userid' => $userid);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_cc} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_cc_cc} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_cmc} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_qa} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_qg} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {course} c
                  JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_recompletion_sst} rc ON rc.course = c.id and rc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }
}
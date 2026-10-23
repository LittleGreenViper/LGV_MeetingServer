<?php
/** Offline import, storage, and JSON flag regression tests. */
declare(strict_types=1);
require __DIR__.'/support/TimezoneFixtures.php';
class FormatFixtureBMLT extends FixtureBMLT {
    protected static function _call_URL($url) {
        if (str_contains($url, 'raw.githubusercontent.com')) {
            return parent::_call_URL($url);
        }
        $formats = [];
        foreach (['WCHR', 'OPEN', 'CLOSED', '', 'OTHER'] as $id => $world) {
            $formats[] = ['id' => (string)($id + 1), 'world_id' => $world,
                'key_string' => ['custom', 'custom2', 'custom3', 'WC', 'O'][$id],
                'name_string' => 'Custom', 'description_string' => '', 'lang' => 'en'];
        }
        return json_encode(['meetings' => self::$meetings, 'formats' => $formats]);
    }
}
$base = ['venue_type' => '2', 'time_zone' => 'Etc/UTC'];
FormatFixtureBMLT::$meetings = [];
foreach (['1,2', '3', '4,5', '', '1,2,3', '99'] as $id => $ids) {
    FormatFixtureBMLT::$meetings[] = $base + ['id_bigint' => (string)($id + 1), 'format_shared_id_list' => $ids];
}
FormatFixtureBMLT::$override_lookup = true;
$meetings = FormatFixtureBMLT::read(NULL);
timezone_check('bmlt:wheelchair_accessible', $meetings[0]['tag0'], 'Custom key uses World ID');
timezone_check('bmlt:open', $meetings[0]['tag1'], 'Open stored');
timezone_check('bmlt:closed', $meetings[1]['tag1'], 'Closed stored');
foreach ([2, 3, 5] as $index) {
    timezone_check(false, isset($meetings[$index]['tag0'], $meetings[$index]['tag1']), 'Unconfirmed formats do not infer flags');
    timezone_check(false, isset($meetings[$index]['tag0']), 'No accessibility inference');
    timezone_check(false, isset($meetings[$index]['tag1']), 'No status inference');
}
FormatFixtureBMLT::$override_lookup = true;
$writer = new FixtureMeetingWriter();
(new FormatFixtureBMLT())->process_all_meetings($writer, 'test_meetings');
timezone_check('bmlt:wheelchair_accessible', $writer->rows[0][11], 'Accessibility persisted');
timezone_check('bmlt:open', $writer->rows[0][12], 'Status persisted');
foreach ($writer->rows as $index => $row) {
    $json = json_decode(json_encode(_clean_meeting(['tag0' => $row[11], 'tag1' => $row[12]])), true);
    $case = $index % 6;
    timezone_check(in_array($case, [0, 4]), $json['wheelchair_accessible'] ?? false, 'Accessibility JSON boolean');
    timezone_check(in_array($case, [0, 4]), $json['is_open'] ?? false, 'Open JSON boolean');
    timezone_check(in_array($case, [1, 4]), $json['is_closed'] ?? false, 'Closed JSON boolean');
    timezone_check(false, isset($json['tag0']) || isset($json['tag1']), 'Internal markers consumed');
}
timezone_check(['tag0' => 'legacy', 'tag1' => '1', 'tag2' => 'other'],
    _clean_meeting(['tag0' => 'legacy', 'tag1' => '1', 'tag2' => 'other']), 'Legacy tags preserved');
echo "Passed $checks format checks.\n";

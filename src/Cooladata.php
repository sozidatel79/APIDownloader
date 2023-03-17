<?php
require_once("File.php");
require_once("Folder.php");
require_once("Constant.php");
require_once ("Utils.php");

class CoolaDataToBigQuery {
    private $request_headers;
    private $curl;
    private $offset;
    private $request;
    private $folder;
    private $process_id;
    private $total_rows;
    private $start_date;
    private $end_date;
    private $time_passed;
    private $time_has_passed;
    private $error;
    private $free_disk_space;

    public function __construct(Curl $curl, $data) {

        $spaceBytes = diskfreespace("/");
        $spaceKb = ($spaceBytes/1024);
        $spaceMb = ($spaceKb/1024);
        $spaceGb = ($spaceMb/1024);
        $this->free_disk_space = (int) $spaceGb;
        $this->start_date = $data['start_date'];
        $this->end_date = $data['end_date'];
        $this->process_id = $data['id'];
        $this->curl = $curl;
        $this->offset = new File('offset' . $this->start_date . '__' . $this->end_date, 'ServiceFiles');
        $this->request = new File('request' . $this->start_date . '__' . $this->end_date, 'ServiceFiles');
        $this->total_rows = new File('total-rows' . $this->start_date . '__' . $this->end_date, 'ServiceFiles');
        $this->time_passed = new File('time' . $this->start_date . '__' . $this->end_date, 'ServiceFiles');
        $this->error = new File('error' . $this->start_date . '__' . $this->end_date, 'ServiceFiles');
        $this->folder = (new Folder(dirname(__DIR__) . '/CoolaDataFiles/' . $this->start_date . '__' . $this->end_date));
        $this->folder->createFolder();

        $this->request_headers = [
            "Authorization:Token " . Constant::COOLADATA_API_TOKEN,
            "ContentType" => "application/urlencode",
        ];

        $this->curl->setHeaders($this->request_headers);
    }

    public function getEventsCount() {

        $events_count_query = "
        SELECT count(event_name) AS events_count 
        FROM cooladata
        WHERE event_name IN(
           'Reshet_page_load',
           'App_page_load',
           'video_video_start_1010',
           'video_video_start_live',
           'video_video_reach_25',
           'video_video_reach_50',
           'Video_content_start_1010',
           'video_video_reach_75',
           'video_video_reach_90',
           'Video_content_start_live',
           'Video_video_reach_100'
          )
        AND event_time_ts BETWEEN " . $this->start_date . " AND ". $this->end_date ."
        ";

        $request_body = [
            "tq"      => $events_count_query,
            "tqx"     => "out:json", // out:csv
            "noCache" => true
        ];

        $events_count = $this->doRequest($request_body);
        return json_decode($events_count)->table->rows[0]->c[0]->v;
    }

    public function getEventsData($chunk_size) {

        $start      = microtime(true);
        $total_rows = $this->total_rows->read();

        if($total_rows == 0) {
            $this->total_rows->write($this->getEventsCount());
        }

        $offset  = $this->offset->read();
        $request = $this->request->read();
        $passed  = $this->time_passed->read();

        if ($this->offset->read() <= ($chunk_size + $total_rows )) {

            $events_query = "
              SELECT  event_name, page_type, page_writer, event_time_ts, page_id, device_os_version, page_Tags,
              device_model, page_1_level_eng, phase, page_2_level_heb, page_3_level_eng, page_has_video,
              page_1_level_heb, page_4_level_heb, page_4_level_eng, page_2_level_eng, page_3_level_heb,
              page_publishdate, page_5_level_eng, page_7_level_heb, page_6_level_eng, page_7_level_eng, page_5_level_heb
              page_6_level_heb, device_screen_size, clean_page_url, device_os_yuval, event_name_yuval, device_type_yuval 
              FROM cooladata
              WHERE event_name IN(
                 'Reshet_page_load',
                 'App_page_load',
                 'Video_video_start_1010',
                 'Video_video_start_live',
                 'Video_video_reach_25',
                 'Video_video_reach_50',
                 'Video_content_start_1010',
                 'Video_video_reach_75',
                 'Video_video_reach_90',
                 'Video_content_start_live',
                 'Video_video_reach_100'
                )
              AND event_time_ts BETWEEN {$this->start_date} AND {$this->end_date}
              LIMIT {$chunk_size} OFFSET {$offset}
            ";

            $request_body = [
                "tq"      => $events_query,
                "tqx"     => "out:json", // out:csv
                "noCache" => true
            ];

            try {

                if($this->free_disk_space <= 1) { // Space in GB
                    $this->error->write('No disk space');
                    $response = [
                        'status'        => 'no_disk_space',
                        'total'         => $total_rows,
                        'remaining'     => abs($this->total_rows->read() - $this->offset->read()),
                        'request'       => 'error',
                        'offset'        => $offset,
                        'between_dates' => $this->start_date . ' | ' . $this->end_date,
                        'chunks'        => 'No disk space',
                        'process_id'    => $this->process_id
                    ];
                    echo json_encode($response);
                    die();
                }

                $row = $this->doRequest($request_body);
                $row_decoded = json_decode($row);

                if($row == '' || $row_decoded->status == 'error') {

                    $this->error->write($row, FILE_APPEND);
                    $response = [
                        'status'        => 'in_process',
                        'total'         => $total_rows,
                        'remaining'     => abs($this->total_rows->read() - $this->offset->read()),
                        'request'       => 'error',
                        'offset'        => $offset,
                        'between_dates' => $this->start_date . ' | ' . $this->end_date,
                        'chunks'        => 'File not written',
                        'process_id'    => $this->process_id
                    ];
                    $response['time'] = $row;

                } else {
                    $offset += $chunk_size;
                    $request += 1;

                    (new File('coola_data', $this->folder->getFolder()))->writeToSeparateFiles($this->folder->getFolder(), $offset.'.json', $row);

                    $response = [
                        'status'        => 'in_process',
                        'total'         => $total_rows,
                        'remaining'     => abs($this->total_rows->read() - $this->offset->read()),
                        'request'       => $request,
                        'offset'        => $this->offset->read(),
                        'between_dates' => $this->start_date . ' | ' . $this->end_date,
                        'chunks'        => $chunk_size,
                        'process_id'    => $this->process_id,
                        'df'            => $this->free_disk_space . ' GB',
                    ];

                    $this->offset->write($offset);
                    $this->request->write($request);

                    $end = microtime(true);
                    $request_time = abs(($end - $start));
                    $this->time_has_passed = $passed + $request_time;
                    $this->time_passed->write($this->time_has_passed);
                    $response['time'] = (int) $request_time;
                    $response['time_passed'] = Utils::secondsToDaysHoursMinutes($this->time_has_passed);
                }
                echo json_encode($response);
                die();
            } catch (Exception $e) {

                $this->curl->close();
                $response = [
                    'status'      => 'error',
                    'time_passed' => 'Request Error'
                ];
                echo json_encode($response);
                die();
            }

        } else {
            $this->curl->close();
            $this->error->write('All tasks done');
            $response = [
                'status'  => 'done',
                'offset'  => $this->offset->read(),
                'message' => "All tasks done",
                'total'   => $this->total_rows,
            ];
            $response['time_passed'] = Utils::secondsToDaysHoursMinutes($this->time_has_passed);
            echo json_encode($response);
            die();
        }
    }

    private function doRequest($request_body) {
        return $this->curl
            ->setParams($request_body)
            ->post(Constant::COOLADATA_URL);
    }
}



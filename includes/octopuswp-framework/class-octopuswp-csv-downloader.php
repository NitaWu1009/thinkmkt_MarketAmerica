<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_Csv_Downloader')) {
    class OctopusWP_Csv_Downloader
    {
        private $csv;

        private function generate_csv($output_data)
        {
            ob_start();
            $stream = fopen('php://output' ,'w');
            fputs($stream, ( chr(0xEF) . chr(0xBB) . chr(0xBF) ) );
            foreach ($output_data as $line ) {
                fputcsv($stream, array_values($line));
            }
            $this->csv = ob_get_clean();
            fclose($stream);
            ob_end_clean();
        }

        public function download($filename, $output_data)
        {
            $this->generate_csv($output_data);
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename* = UTF-8''" . urlencode("{$filename}.csv"));
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $this->csv;
            exit;
        }
    }
}

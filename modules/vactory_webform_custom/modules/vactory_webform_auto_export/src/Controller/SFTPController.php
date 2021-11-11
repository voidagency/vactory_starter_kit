<?php
namespace Drupal\vactory_webform_auto_export\Controller;

use phpseclib\Net\SFTP;

/**
 * Class SFTPController
 */
class SFTPController
{

    /**
     * SFTPController constructor.
     */
    public function __construct()
    {

    }

    /**
     * Send file via SFTP
     *
     * @param $sftp_details
     * @param $source_path
     * @param $file_name
     * @param int $mode
     * @return bool
     */
    public static function send_file($sftp_details, $source_path, $file_name, $mode = SFTP::SOURCE_LOCAL_FILE)
    {
        $host = $sftp_details['host'];
        $username = $sftp_details['username'];
        $password = $sftp_details['password'];

        $sftp = new SFTP($host);
        if (!$sftp->login($username, $password)) {
            exit('Login Failed');
        }

        return $sftp->put($sftp_details['destination'] . '\\' . $file_name, $source_path, $mode);
    }
}

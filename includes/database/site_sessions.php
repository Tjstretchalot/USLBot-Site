<?php
  require_once 'database/common.php';

  class SiteSession {
    public $id;
    public $session_id;
    public $person_id;
    public $created_at;
    public $expires_at;

    public function __construct($id, $session_id, $person_id, $created_at, $expires_at) {
      $this->id = $id;
      $this->session_id = $session_id;
      $this->person_id = $person_id;
      $this->created_at = $created_at;
      $this->expires_at = $expires_at;
    }

    public static function from_assoc_row($row) {
      $expires_at = $row['expires_at'];
      if($expires_at !== null) {
	$expires_at = strtotime($expires_at);
      }
      return new SiteSession($row['id'], $row['session_id'], $row['person_id'], strtotime($row['created_at']), $expires_at);
    }
  }

  class SiteSessionMapping {
    public static function create_and_save($sql_conn, $session_id, $person_id, $created_at, $expires_at) {
      $insert_created_at = $created_at;
      $insert_expires_at = $expires_at;

      if(gettype($insert_created_at) == 'integer') {
	$insert_created_at = date('Y-m-d H:i:s', $insert_created_at);
      }

      if(gettype($insert_expires_at) == 'integer') {
	$insert_expires_at = date('Y-m-d H:i:s', $insert_expires_at);
      }

      if(gettype($created_at)
      $err_prefix = 'SiteSessionMapping#create_and_save';
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare('INSERT INTO site_sessions (session_id, person_id, created_at, expires_at) VALUES (?, ?, ?, ?)');
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('siss', $session_id, $person_id, $insert_created_at, $insert_expires_at));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $site_sess = new SiteSession($sql_conn->insert_id, $session_id, $person_id, strtotime($insert_created_at), strtotime($insert_expires_at));
      $stmt->close();
      return $site_sess;
    }

    public static function fetch_by_session_id($sql_conn, $session_id) {
      $err_prefix = 'SiteSessionMapping#fetch_by_session_id';
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare('SELECT * FROM site_sessions WHERE session_id=?');
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('s', $session_id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $site_sess = null;
      $row = $res->fetch_assoc();
      if($row) {
	$site_sess = SiteSession::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $site_sess;
    }

    public static function delete_by_id($sql_conn, $id) {
      $err_prefix = 'SiteSessionMapping#delete_by_id';
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare('DELETE FROM site_sessions WHERE id=?'));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('i', $id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      $stmt->close();
    }
  }
?>

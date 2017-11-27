<?php
  require_once 'database/common.php';

  class RegisterAccountRequest {
    public $id;
    public $person_id;
    public $token;
    public $consumed;
    public $created_at;
    public $sent_at;

    public function __construct($id, $person_id, $token, $consumed, $created_at, $sent_at) {
      $this->id = $id;
      $this->person_id = $person_id;
      $this->token = $token;
      $this->consumed = $consumed;
      $this->created_at = $created_at;
      $this->sent_at = $sent_at;
    }

    public function from_assoc_row($row) {
      return new RegisterAccountRequest($row['id'], $row['person_id'], $row['token'], $row['consumed'], strtotime($row['created_at']), strtotime($row['sent_at']));
    }
  }

  class RegisterAccountRequestMapping {
    public static function fetch_by_id($sql_conn, $id) {
      $err_prefix = "RegisterAccountRequestMapping#fetch_by_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM reg_account_requests WHERE id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $request = null;
      $row = $res->fetch_assoc();
      if($row) {
	$request = RegisterAccountRequest::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $request;
    }

    public static function fetch_latest_by_person_id($sql_conn, $person_id) {
      $err_prefix = "RegisterAccountRequestMapping#fetch_latest_by_person_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM reg_account_requests WHERE person_id=? ORDER BY created_at ASC LIMIT 1"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $request = null;
      $row = $res->fetch_assoc();
      if($row) {
	$request = RegisterAccountRequest::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $request;
    }

    public static function insert_row($sql_conn, $request) {
      $err_prefix = "RegisterAccountRequestMapping#insert_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("INSERT INTO reg_account_requests (person_id, token, consumed, created_at, sent_at) VALUES (?, ?, ?, ?, ?)"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('isiss', $request->person_id, $request->token, $request->consumed, date('Y-m-d H:i:s', $request->created_at), date('Y-m-d H:i:s', $request->sent_at)));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      $request->id = $sql_conn->insert_id;

      $stmt->close();
    }

    public static function update_row($sql_conn, $request) {
      $err_prefix = "RegisterAccountRequestMapping#update_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("UPDATE reg_account_requests SET person_id=?, token=?, consumed=?, created_at=?, sent_at=? WHERE id=?");
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('isiss', $request->person_id, $request->token, $request->consumed, date('Y-m-d H:i:s', $request->created_at), date('Y-m-d H:i:s', $request->sent_at), $request->id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $stmt->close();
    }
  }
?>

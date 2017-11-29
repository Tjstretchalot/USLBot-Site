<?php
  require_once 'database/common.php';

  class ResetPasswordRequest {
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

    public static function from_assoc_row($row) {
      return new ResetPasswordRequest($row['id'], $row['person_id'], $row['token'], $row['consumed'], strtotime($row['created_at']), strtotime($row['sent_at']));
    }
  }

  class ResetPasswordRequestMapping {
    public static function fetch_by_person_id($sql_conn, $person_id) {
      $err_prefix = "ResetPasswordRequestMapping#fetch_by_person_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM reset_password_requests WHERE person_id=? ORDER BY created_at DESC LIMIT 1"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $person_id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $rpr = null;
      $row = $res->fetch_assoc();
      if($row) {
	$rpr = ResetPasswordRequest::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $rpr;
    }

    public static function insert_row($sql_conn, $rpr) {
      $usable_created_at = null;
      if($rpr->created_at !== null) {
	$usable_created_at = date('Y-m-d H:i:s', $rpr->created_at);
      }

      $usable_sent_at = null;
      if($rpr->sent_at !== null) {
	$usable_sent_at = date('Y-m-d H:i:s', $rpr->sent_at);
      }

      $err_prefix = "ResetPasswordRequestMapping#insert_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("INSERT INTO reset_password_requests (person_id, token, consumed, created_at, sent_at) VALUES (?, ?, ?, ?, ?)"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("isiss", $rpr->person_id, $rpr->token, $rpr->consumed, $usable_created_at, $usable_sent_at));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $rpr->id = $sql_conn->insert_id;

      $stmt->close();
    }

    public static function update_row($sql_conn, $rpr) {
      $usable_created_at = null;
      if($rpr->created_at !== null) {
	$usable_created_at = date('Y-m-d H:i:s', $rpr->created_at);
      }

      $usable_sent_at = null;
      if($rpr->sent_at !== null) {
	$usable_sent_at = date('Y-m-d H:i:s', $rpr->sent_at);
      }

      $err_prefix = "ResetPasswordRequestMapping#update_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("UPDATE reset_password_requests SET person_id=?, token=?, consumed=?, created_at=?, sent_at=? WHERE id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("isissi", $rpr->person_id, $rpr->token, $rpr->consumed, $usable_created_at, $usable_sent_at, $rpr->id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $stmt->close();
    }
  }

?>

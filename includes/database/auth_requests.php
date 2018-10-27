<?php
  class TemporaryAuthorizationRequest {
    public $id;
    public $person_id;
    public $created_at;

    public function __construct($id, $person_id, $created_at) {
      $this->id = $id;
      $this->person_id = $person_id;
      $this->created_at = $created_at;
    }

    public static function from_assoc_row($row) {
      return new TemporaryAuthorizationRequest($row['id'], $row['person_id'], strtotime($row['created_at']));
    }
  }

  class TemporaryAuthorizationRequestMapping {
    public static function fetch_by_person_id($sql_conn, $person_id) {
      $err_prefix = "TemporaryAuthorizationRequestMapping#fetch_by_person_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM temporary_auth_requests WHERE person_id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $person_id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $level = null;
      $row = $res->fetch_assoc();
      if($row) {
	$level = TemporaryAuthorizationRequest::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $level;
    }

    public static function create($sql_conn, $person_id) {
      $err_prefix = "TemporaryAuthorizationRequestMapping#create";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("INSERT INTO "));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $person_id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      $stmt->close();
    }
  }
?>

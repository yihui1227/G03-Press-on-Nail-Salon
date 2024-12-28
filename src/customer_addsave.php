<?php
  $name= $_POST["name"];
  $phone = $_POST["phone"];
  $leftsize = $_POST["leftsize"];
  $rightsize = $_POST["rightsize"];
  
  if($name == ''){
	 $name = '修改顧客名稱';
  }
  $leftsize = json_encode($leftsize);
  $rightsize = json_encode($rightsize);
  $sql = "INSERT INTO customer (name, phone, leftsize, rightsize) VALUES (?, ?, ?, ?)";
  if($stmt = $db->prepare($sql)){
  $success = $stmt->execute(array($name, $phone, $leftsize, $rightsize));
  
  if (!$success) {
    echo "儲存失敗!".$stmt->errorInfo();
  }else{
  	$ToyID = $db->lastInsertId();
  }
  }
?>
<?php
require 'database.php';

if($_GET["API_KEY"]=="Flight booker 1.2 plus edition"){
    switch($_GET["action"]){
        case "get":Get();break;
        case "login":Login();break;
        case "book":Book();break;
        default:break;
    }
}
else{
    echo "wrong Key";
}

function Get(){
    global $conn;
    switch($_GET["items"]){
        case "airport":
            $search = $_GET['search'];
            $records = $conn->prepare('SELECT * FROM airports WHERE airport LIKE :search OR code LIKE :search');
            $records->bindValue(':search', '%' . $search . '%');
            $records->execute();
            $results = $records->fetchAll(PDO::FETCH_ASSOC);
            if(count($results) > 0) {
                $obj=["status"=>"200","statusDescription"=>"Ok"];
                array_unshift($results,$obj);
                $jsonObj= json_encode($results);
            }
            else {
                $obj=["status"=>"440","statusDescription"=>"No matching airports found"];
                $jsonObj="[".json_encode($obj)."]";
            }
            echo $jsonObj;
            break;
        case "flights":
            $date = $_GET['date'];
            $from = $_GET['from'];
            $to = $_GET['to'];
            $records = $conn->prepare('SELECT * FROM flights WHERE (substring(date_time_depart,1,10) = :date OR substring(date_time_arriv,1,10) = :date) AND airport_from = :from AND airport_to = :to');
            $records->bindParam(':date', $date);
            $records->bindParam(':from', $from);
            $records->bindParam(':to', $to);
            $records->execute();
            $results = $records->fetchAll(PDO::FETCH_ASSOC);
            if(count($results) > 0) {
                $obj=["status"=>"200","statusDescription"=>"Ok"];
                array_unshift($results,$obj);
                $jsonObj= json_encode($results);
            }
            else {
                $obj=["status"=>"460","statusDescription"=>"No matching flights found"];
                $jsonObj="[".json_encode($obj)."]";
            }
            echo $jsonObj;
    }
}

function Login(){
    global $conn;
    $email = $_GET['email'];
    $pass = md5($_GET['pass']);
    
    $records = $conn->prepare('SELECT * FROM passengers WHERE email = :email AND password = :pass');
    $records->bindParam(':email', $email);
    $records->bindParam(':pass', $pass);
    $records->execute();
    $results = $records->fetchAll(PDO::FETCH_ASSOC);

    if(count($results) > 0) {
        $token = md5(rand());
        $stmt = $conn->prepare('UPDATE passengers SET token = :token WHERE email = :email');
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);

        if($stmt->execute()) {
            $results[0]["token"] = $token;
        }
        $obj=["status"=>"200","statusDescription"=>"Ok"];
        array_unshift($results,$obj);
        $jsonObj= json_encode($results);
    }
    else {
        $obj=["status"=>"450","statusDescription"=>"No user found"];
        $jsonObj="[".json_encode($obj)."]";
    }
    echo $jsonObj;
}

function Book(){
    global $conn;
    $passenger = $_GET['passenger'];
    $token = $_GET['token'];
    $flight = $_GET['flight'];

    $records = $conn->prepare('SELECT * FROM passengers WHERE id = :passenger AND token = :token');
    $records->bindParam(':passenger', $passenger);
    $records->bindParam(':token', $token);
    $records->execute();
    $results = $records->fetchAll(PDO::FETCH_ASSOC);

    if(count($results) > 0) {
        $stmt = $conn->prepare('INSERT INTO bookings (passenger, flight, status) VALUES (:passenger, :flight, "OK")');
        $stmt->bindParam(':passenger', $passenger);
        $stmt->bindParam(':flight', $flight);

        if ($stmt->execute()){
            $obj=["status"=>"200","statusDescription"=>"OK"];
            $jsonObj="[".json_encode($obj)."]";
        }
        else{
            $obj=["status"=>"460","statusDescription"=>"problem with the booking"];
            $jsonObj="[".json_encode($obj)."]";
        }
    }
    else {
        $obj=["status"=>"470","statusDescription"=>"User token problem"];
        $jsonObj="[".json_encode($obj)."]";
    }
    echo $jsonObj;
}

?>

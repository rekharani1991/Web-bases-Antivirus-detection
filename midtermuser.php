<?php
require_once "login.php";
$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) die(mysql_fatal_err()); //if there is an error in connection, function mysql_fatal_err would be called


//webpage to allow user to upload file to check whether it contains a virus or not
echo <<<_END
<html><head><title>students</title></head>
<body>
<form method='post' action='midtermuser.php' enctype='multipart/form-data'><pre>
PLEASE UPLOAD TO FILE CHECK WHEHTER IT CONTAINS A VIRUS OR NOT :)
----------------------------
Select file: <input type='file' name='filename' size='20'>
<br>
<input type='submit' value='upload'>
</form>
_END;

// checking the uploded file and santitzing it and checking whether signature is stored in database or not
if ($_FILES) {
    $filename = $_FILES['filename']['name'];
    // sanitize the file name
    $filename = santitize_file_contents("[^A-Za-z0-9.]", "", $filename);
    $lengthOfFile = strlen(file_get_contents($_FILES['filename']['tmp_name']));

    // if file contains less than 20 bytes, it would not contain virus
    if ($lengthOfFile < 20) {
        echo " \n Congratulations !!!!!!!  File is too small to contain a virus !!!!! ";
    } else {
        $data = file_get_contents($_FILES['filename']['tmp_name']);
        $data = santitize_file_contents($data);

        // databse query to check whether the file contains a virus or not
        $query = "SELECT * FROM virusdb";
        $result = $conn->query($query);
        if (!$result) {
            die(mysql_fatal_err());
        }
        $rows = $result->num_rows;
        if ($rows == 0) {
            echo " \n Hey !! Congratulations !!!! no virus found.... :) ";
        } else {
            $check = 0;
            for ($i = 0; $i < $rows; $i += 1) {
                $result->data_seek($i);
                $row = $result->fetch_array(MYSQLI_NUM);
                if (strpos($data, $row[1]) !== false) {
                    echo "\n virus found : $row[0]";
                    $check = $check + 1;
                    break;
                }
            }
            if ($check == 0) {
                echo " \n Congratualtions !!!! NO VIRUS FOUND  ";
            }
        }
        $result->close();

    }
}

//closing the connection
$conn->close(); // closing the connection



//FUNCTIONS USED: 

// Function that would be called when there is a connection error.
function mysql_fatal_err()
{
    echo <<<_END
    </br>OOPS !!!!!!
    </br>We are sorry, but it was not possible to complete
    </br>the requested task.
    </br>
    _END;
}


function santitize_file_contents($var)
{
    if (get_magic_quotes_gpc())
        $var = stripslashes($var);
    $var = strtolower(preg_replace("[^A-Za-z0-9.]", "", $var));
    $var = strip_tags($var);
    $var = htmlentities($var);
    return $var;
}

//Preventing HTML injections
function mysql_entities_fix_string($conn, $var)
{
    return htmlentities(mysql_fix_string($conn, $var));
}

//The function below will remove any “magic quotes” and  properly sanitize it for you.
function mysql_fix_string($conn, $var)
{
    if (get_magic_quotes_gpc())
        $var = stripslashes($var);
    $var = strip_tags($var);
    return $conn->real_escape_string($_POST[$var]);
}

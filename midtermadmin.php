<?php
require_once "login.php";
$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die(mysql_fatal_err());
} //if there is an error in connection, function mysql_fatal_err would be called
// when advisor name, student name, id, and class code is set

// fetching the username and password from the admin table to verify it
// this password is hashed with a salt added to it
$query = "SELECT * FROM admin";
$result = $conn->query($query);
if (!$result) {
    die(mysql_fatal_err());
} elseif ($result->num_rows) {
    $row = $result->fetch_array(MYSQLI_NUM);
    $password = $row[2];
    $username = $row[0];
}
$result->close();


// HTTTP authentication for admin credentials
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    // if the hashed and salted password mathches the hashed and salted password from user,
    // password_verify would return "success"
    $x = 'success';
    $user = mysql_fix_string($conn,$_SERVER['PHP_AUTH_USER']);
    $pass = mysql_fix_string($conn,$_SERVER['PHP_AUTH_PW']);
    $verify_password = password_verify($pass, $password);

    if ($user == $username && $verify_password == $x) {
        echo "You are now logged in \n";
    } else {
        die("Invalid username / password combination\n");
    }
} else {
    header('WWW-Authenticate: Basic realm="Restricted Section"');
    header('HTTP/1.0 401 Unauthorized');
    die("Please enter your username and password\n");
}

// Web page to upload a signature file by admin
echo <<<_END
<html><head><title>students</title></head>
<body>
<br>
<form method='post' action='midtermadmin.php' enctype='multipart/form-data'><pre>
Hello Admin ! :)
----------------

<b>Upload the virus signature file  :)</b>
----------------------------

Name of the virus: <input type='text' name='virusname' size ='20'><br>
Select file: <input type='file' name='filename' size='20'><br>
<input type='submit' value='upload'><br>
</form>
_END;





// checking the uploded file and santitzing it and inserting into database.
if (isset($_POST['virusname'])) {
    if ($_FILES) {
        $filename = $_FILES['filename']['name'];
        // sanitize the file name
        $filename = santitize_file_contents("[^A-Za-z0-9.]", "", $filename);
        $lengthOfFile = strlen(file_get_contents($_FILES['filename']['tmp_name']));
        if ($lengthOfFile < 20) {
            echo " \n Sorry !!!!!!!  File is too small to become a virus signature file  !!!!! n";
        } else {
            $handle = fopen($_FILES['filename']['tmp_name'], 'r');
            if (false === $handle) {
                exit("Failed to open file ");
            }
            // santitizing the file contensts
            $data = santitize_file_contents(fread($handle, 20));

            fclose($handle);
            // santitizing the virusname recieved from admin
            $virusname = santitizeSuper($conn, 'virusname');
            echo $virusname;
            inserting($conn, $virusname, $data);
        }
    }
}
else{
    echo "No upload till now !! ";
}

$conn->close(); // closing the connection




//FUNCTIONS USED:

//function to handle the insert query : using Placeholders
function inserting($conn, $virusname, $data)
{
    $stmt = $conn->prepare('INSERT INTO virusdb VALUES(?,?)');
    $stmt->bind_param('ss', $virusname, $data);
    $stmt->execute();
    printf("\n%d Row inserted.\n", $stmt->affected_rows);
    $stmt->close();
}

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
// function to santitize the file contents.
function santitize_file_contents($var)
{
    if (get_magic_quotes_gpc()) {
        $var = stripslashes($var);
    }
    $var = strtolower(preg_replace("[^A-Za-z0-9.]", "", $var));
    $var = strip_tags($var);
    $var = htmlentities($var);
    return $var;
}
//santitzation function before inserting into database.
function santitizeSuper($conn, $var)
{
    if (get_magic_quotes_gpc()) {
        $var = stripslashes($var);
    }
    $var = strip_tags($var);
    $temp = $conn->real_escape_string($_POST[$var]);
    return htmlentities($temp);
}


//Preventing HTML injections
function mysql_entities_fix_string($conn, $var)
{
    return htmlentities(mysql_fix_string($conn, $var));
}

//The function below will remove any “magic quotes” and  properly sanitize it for you.
function mysql_fix_string($conn, $var)
{
    if (get_magic_quotes_gpc()) {
        $var = stripslashes($var);
    }
    $var = strip_tags($var);
    return $conn->real_escape_string($var);
}


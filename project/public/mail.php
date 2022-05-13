<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mail page</title>
</head>
<body>
    <h1>Mail page</h1>
    <p>Send mail automatically</p>
    <div>
        <?php
            $dest="fake@mail.net";
            $object="subject of mail via PHP";
            $message="content of mail via PHP";
            $headers="From: noreply@example.net"; // TODO: check if can be different of revaliases [SERVICE-NAME]@[DOMAIN.EXT]
            $headers.="Content-Type: text/html; charset=utf-8";
            
            if(mail($dest,$object,$message)) // TODO: check if can use $headers
            echo "Mail '" . $object . "' successfully sended.";
            else
            echo "A problem has occurred";
            exit;
        ?>
    </div>

    <a href="index.php">Index</a>
</body>
</html>
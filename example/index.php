<?php
require('ae.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Example - Activation Engine</title>
    <link rel="stylesheet" type="text/css" href="<?php echo($cssurl); ?>">
    <script type="text/javascript" src="../js/jquery-1.10.2.min.js"></script>
    <script type="text/javascript">
        // Check if the page has loaded completely
        $(document).ready( function() {
            setTimeout( function() {
                $('#some_id').load('index.php');
            }, 10000);
        });
    </script>


        <body>


    <div id="some_id"><?php ae_callurl();?></div>

        </body>
</head>
</html>
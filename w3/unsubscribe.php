<?php
$email='';
if (isset($_REQUEST['md_email'])) {
    $email = $_REQUEST['md_email'];
}
?>
<html>
<body bgcolor="#E0E0E0">

<table width="100%" align="center">
    <tr>
        <td style="font-family: Arial; font-size: 14pt; font-weight: bold;" align="center" width="100%">
            <br /> &nbsp;
            <br />
            <?php echo $email?>
        </td>
    </tr>
    <tr>
        <td align="center" width="100%">
            <br />
            This email address has been unsubscribed
        </td>
    </tr>
</table>

</body>
</html>

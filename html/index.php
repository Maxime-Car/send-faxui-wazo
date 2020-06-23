<?php
$wazo_ip_addr='192.168.0.0';
$wazo_fax_user='+WAZO-FAX-USER+';
$wazo_fax_passwd='+WAZO-FAX-PASSWD+';
$target_dir = "uploads/";
$msg_fin_transmision="";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$destinataire = preg_replace('/\D/', '', $_POST['phonenumber']);
$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
  if ($_FILES["fileToUpload"]["size"] > 2000000) {
      $msg_fin_transmision="La taille est limitée à 2Mb.";
      $uploadOk = 0;
  }
  // Allow certain file formats
  if($fileType != "pdf" && $fileType != "PDF") {
      $msg_fin_transmision="Seuls les fichiers PDFs sont autorisés.";
      $uploadOk = 0;
  }
  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 0) {
      $msg_fin_transmision="Fichier invalide. Veuillez réessayer. Si le problème persiste, contacter le support informatique.";

  // if everything is ok, try to upload file
  } else {
      if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
          $msg_fin_transmision="Votre fichier ". basename( $_FILES["fileToUpload"]["name"]). " a été importé et est prêt à l'envoie.";
      } else {
          $msg_fin_transmision="Upload impossible. Veuillez réessayer. Si le problème persiste, contacter le support informatique.";
          $uploadOk = 2;
      }
  }

  if ($uploadOk == 1) {

    // Using PHP-Curl to get an authentication Token
    $token_request = curl_init();

    curl_setopt($token_request, CURLOPT_URL, 'https://'.$wazo_ip_addr.'/api/auth/0.1/token');
    curl_setopt($token_request, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($token_request, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($token_request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($token_request, CURLOPT_POST, 1);
    curl_setopt($token_request, CURLOPT_POSTFIELDS, "{\"backend\": \"wazo_user\",\"expiration\": 120}");
    curl_setopt($token_request, CURLOPT_USERPWD, $wazo_fax_user . ':' . $wazo_fax_passwd);

    $token_headers = array();
    $token_headers[] = 'Content-Type: application/json';
    curl_setopt($token_request, CURLOPT_HTTPHEADER, $token_headers);

    $token_result = curl_exec($token_request);
    if (curl_errno($token_request)) {
        echo 'Error:' . curl_error($token_request);
    }
    curl_close($token_request);

    $json_result = json_decode($token_result, true);

    // Parsing response to get token
    $token = $json_result['data']['token'];

    // Using PHP-Curl to send the pdf file and asking to send a fax
    $send_fax_request = curl_init();
    $pdf_file = new CURLFile(realpath($target_file),'application/pdf');

    curl_setopt($send_fax_request, CURLOPT_URL, 'https://'.$wazo_ip_addr.':9500/1.0/users/me/faxes?extension='.$destinataire.'&caller_id=+CALLER-ID+');
    curl_setopt($send_fax_request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($send_fax_request, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($send_fax_request, CURLOPT_SSL_VERIFYHOST, false);
    $post = array('pdf' => $pdf_file);
    curl_setopt($send_fax_request, CURLOPT_POST, 1);
    curl_setopt($send_fax_request, CURLOPT_POSTFIELDS, $post);

    $headers = array();
    $headers[] = 'Content-Type: application/pdf';
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Auth-Token: '.$token;
    curl_setopt($send_fax_request, CURLOPT_HTTPHEADER, $headers);

    $fax_result = curl_exec($send_fax_request);

    if(!curl_errno($send_fax_request)) {
      $send_fax_request_info = curl_getinfo($send_fax_request);

      $msg_fin_transmision='Code de retour : ' . $send_fax_request_info['http_code'] . ' (201=Succès). La requête a mis ' . $send_fax_request_info['total_time'] . ' secondes à être envoyée au serveur de Fax';
    } else {
      $msg_fin_transmision='Une erreur s\'est produite : ' . curl_error($send_fax_request).' .Contacter le support informatique si le problème persiste.';
    }

    curl_close($send_fax_request);

    $json_fax_result = json_decode($fax_result, true);

    // Deleting the uploaded file
    unlink($target_file);
  }
}
?>


<!DOCTYPE html>
<html lang="fr">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Colorlib Templates">
    <meta name="author" content="Colorlib">
    <meta name="keywords" content="Colorlib Templates">

    <title>send-faxui-wazo - Send a fax</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i" rel="stylesheet">

    <link href="css/main.css" rel="stylesheet" media="all">
    <link rel="icon" type="image/png" href="vendor/img/wazo-white.png" />
  </head>

  <body class="bg-blue">
    <div class="page-wrapper p-t-100 p-b-50">
        <div class="wrapper wrapper--w900">
            <div class="card card-6">
                <div class="card-heading">
                    <img src="vendor/img/wazo-white.png" height="20" alt="Logo Wazo Paltform"/>
                    <h2 class="title">send-faxui-wazo - +WAZO-FAX-NAME+</h2>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="">
                        <div class="form-row">
                            <div class="name">Destinataire</div>
                            <div class="value">
                                <div class="input-group">
                                    <input class="input--style-6" type="tel" name="phonenumber" placeholder="0123456789" pattern="[0-9]{10}" required />
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="name">PDF à faxer</div>
                            <div class="value">
                                <div class="input-group js-input-file">
                                    <input class="input-file" type="file" name="fileToUpload" id="fileToUpload" accept="application/pdf" required/>
                                    <label class="label--file" for="fileToUpload">Choisir un fichier</label>
                                    <span class="input-file__info">Pas de fichier selectionné</span>
                                </div>
                                <div class="label--desc">Selectionner un <b>fichier PDF en A4 format portrait</b>. Taille maximale 2 MB.</div>
                            </div>
                        </div>
                        <div class="form-row">
                          <input class="btn btn--radius-2 btn--blue-2" type="submit" name="submit" value="Envoyer le fax" />
                          <p><b><?php echo $msg_fin_transmision; ?></b></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/global.js"></script>
  </body>
</html>

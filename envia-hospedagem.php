<?php


$str = file_get_contents('formulario.json');
$json = json_decode($str, true);
$secreteKey = $json["formulario"]["reCaptchaSecret"];

function findCampo($json, $id){
	
	
	foreach($json["formulario"]["campo"] as $key => $val){
		
		if($val["id"] == $id){
			return $val;
		}
		
	}
	
	return null;
	
	
}

function findEmailResposta($json){		
	foreach($json["formulario"]["campo"] as $key => $val){		
		if($val["responderParaEsteEmail"] == true){
			return $val;
		}		
	}
	return null;
}

function findNome($json){		
	foreach($json["formulario"]["campo"] as $key => $val){		
		if($val["EsteEhONome"] == true){
			return $val;
		}		
	}
	return null;
}

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = json_decode(curl_exec($curl));
    

    curl_close($curl);

    return $result;
}


$resultado = CallAPI("POST", "https://www.google.com/recaptcha/api/siteverify", 
	array(
			"secret" => $secreteKey, 
			"response" => $_POST["g-recaptcha-response"], 
			"remoteip" => $_SERVER['REMOTE_ADDR']
		)
	);

//	
$siteempresa = $json["formulario"]["siteRedirecionamento"];
$respostaDeEnvio = $json["formulario"]["respostaDeEnvio"];

if($resultado->success){
	
	$mailTo = $json["formulario"]["emailDestino"];

	$campoEmailResposta = findEmailResposta($json);
	$campoNome = findNome($json);
	
	$fromName = $_POST[$campoNome["id"]];
	$mailReplyTo = $_POST[$campoEmailResposta["id"]];
	
	$mailFrom = $mailReplyTo;


	$mensagem ="<br />";
	
	$mailSubject =  $json["formulario"]["assunto"];
	
	
	
	foreach($json["formulario"]["campo"] as $key => $valor){
		
		
		if($valor["tipo"] == "IniciaSessao"){
			
			$mensagem .="
			<fieldset style='border:0'><legend><h3>#{$valor["label"]}</h3></legend>
			";
			
		} else if($valor["tipo"] == "EncerraSessao"){
			
			$mensagem .="
			</fieldset>
			";
			
		} else {
			
			if($valor["tipo"] != "File"){
			
				$val = $_POST[$valor["id"]];
				
		
				$mailSubject = str_replace('{$'.$valor["id"].'}',$val,$mailSubject);
				
				if(is_array($val)){
					$texto = join($val, ", ");
				} else {
					$texto = $val;
				}
				
				$mensagem .="
				    <p><strong>{$valor["id"]}:</strong> $texto </p>";
			}
		
		}
	}
	
	/*
	foreach($_POST as $key => $val){
		var_dump($$key);
		$mailSubject = str_replace($$key,$val,$mailSubject);
		
		if(is_array($val)){
			$texto = join($val, ", ");
		} else {
			$texto = $val;
		}
		$mensagem .="
		<p>
		    <b>$key:</b> 
		    $texto
		</p>
		";
	}*/

	// Recipients


	// From

	$mailFromName = "Formulario de Contato";

	// Files

	foreach($_FILES as $key => $val){
		
		$campo = findCampo($json, $key);
		
		
		$allowed = explode(",", $campo["extensoes"]);
		
		
		
		$filename = $val['name'];
		$ext = trim(".".pathinfo($filename, PATHINFO_EXTENSION));
	
		
		if( in_array($ext,$allowed) ) {				
	        $fname[]       = $val['name'];
	        $tmpName[]       = $val['tmp_name'];
	        $formName[]       = $key;
		} else {
			$mensagem .="
			
		<p>
		    <b>ARQUIVO NÃO PROCESSADO ($key): <br />
		    {$val['name']}
		</p>";
		}
	} 

	// Message subject and contents
	
	
	
	$mailMessage = $mensagem;

	if(!$json["formulario"]["usarHtmlNoEmail"]){
		$mailMessage = strip_tags($mailMessage);
	}

	$boundary = "XYZ-" . date("dmYis") . "-ZYX";
	
	
	// boundary 
    $semi_rand = md5(time()); 
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
    
	$headers = "From: $fromName <$mailFrom>\r\nReply-to: $mailFrom";
	$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 

    $message = 	"This is a multi-part message in MIME format.\n\n" . 
    			"--{$mime_boundary}\n" . 
    			"Content-Type: text/html; charset=\"iso-8859-1\"\n" . 
    			"Content-Transfer-Encoding: 7bit\n\n" . $mailMessage . "\n\n"; 
    $message .= "--{$mime_boundary}\n";
    
       
	
    // array with filenames to be sent as attachment
    $files = $fname;
    
        // preparing attachments
    for($x=0;$x<count($files);$x++){
        $file = fopen($tmpName[$x],"rb");
        $data = fread($file,filesize($tmpName[$x]));
        fclose($file);
        $data = chunk_split(base64_encode($data));
        $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"$files[$x]\"\n" . 
        "Content-Disposition: attachment;\n" . " filename=\"$formName[$x]-$files[$x]\"\n" . 
        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
        $message .= "--{$mime_boundary}\n";
    }
    
    

	
	// Send mail
	try{
		
		
		mail($mailTo, $mailSubject, $message, $headers);
		
		exit ("
		
<head>	
<link rel='stylesheet' type='text/css' href='assets/bootstrap.css' />
<link rel='stylesheet' type='text/css' href='assets/mdb.min.css' />
<link rel='stylesheet' type='text/css' href='assets/style.min.css' />
</head>

<div class='card text-center' style='margin:0 auto; width: 50%;'>
    <div class='card-header success-color white-text'>
        Sucesso!
    </div>
    <div class='card-body'>
        <h4 class='card-title'>Envio Realizado Com Sucesso</h4>
        <p class='card-text'>$respostaDeEnvio</p>
        <a href='$siteempresa' class='btn btn-success btn-sm'>Voltar</a>
    </div>
    <div class='card-footer text-muted success-color white-text'>
        <p class='mb-0'>$siteempresa</p>
    </div>
</div>

		");
		
		
	} catch (Exception $e) {
		
	}
	


} else {	
	
	exit ("
<head>	
<link rel='stylesheet' type='text/css' href='assets/bootstrap.css' />
<link rel='stylesheet' type='text/css' href='assets/mdb.min.css' />
<link rel='stylesheet' type='text/css' href='assets/style.min.css' />
</head>
<div class='card text-center' style='margin:0 auto; width: 50%;'>
    <div class='card-header danger-color white-text'>
        Erro!
    </div>
    <div class='card-body'>
        <h4 class='card-title'>Envio Não Realizado</h4>
        <p class='card-text'>Não foi possível validar o reCaptcha. Por favor, tente novamente!</p>
        <a href='$siteempresa' class='btn btn-danger btn-sm'>Voltar</a>
    </div>
    <div class='card-footer text-muted danger-color white-text'>
        <p class='mb-0'>$siteempresa</p>
    </div>
</div>

		");
		
}

?>
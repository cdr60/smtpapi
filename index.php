<?php
require_once ("./phpmailer/PHPMailerAutoload.php");
#######################################################
# API DISTANTE D'ENVOI DE MAIL                        #
# METHODE : POST                                      #
# VARIABLES :                                         #
# action = new/putcc/putbcc/addfile/send              #
# si putcc/putbcc/addfile alors :                     #
#              idmail doit contenir l'id du mail      #
#######################################################
function GetVariableFrom ($from,$name,$default="") 
{
	if (!is_array($from)) $from=array();
	if (!isset($from[$name]))   $from[$name]=$default;
	elseif (($from[$name]=="") and ($default!="")) $from[$name]=$default;
    return $from[$name];
}

function filterlist($string) 
{
   $tb=explode(";",$string);
   if (count($tb)==0) return False;
   $r=array();
   for ($i=0;$i<count($tb);$i++)
   {
	   if (filter_var($tb[$i], FILTER_VALIDATE_EMAIL)==True) $r[]=$tb[$i];
   }
   return $r;
}

function SqlString($String,$Upper)
{
	$String =str_replace(chr(160),chr(32),$String);
    $String=trim($String);
	if ($String=="") return "null";
    $String = strip_tags ($String);
    $String = html_entity_decode($String,ENT_QUOTES);
	$String = str_replace("\"", "'", $String);
	$String = str_replace("\"", "'", $String);
	$String = str_replace("\'", "'", $String);
	$String = str_replace("&#8216;", "'", $String);
    $String = str_replace("&#8217;", "'", $String);
	$String = str_replace("\\\\", "\\", $String);
    $String = str_replace("''", "'", $String);
    $String = str_replace("'", "''", $String);
    $String = rtrim($String);
	If ($Upper==TRUE)
			$String=strtoupper($String);
    return "'".$String."'";
}

function SqlInteger($String,$nullable)
{
	if ((strval($String)=="") and ($nullable==TRUE))
		$resultat="null";
	elseif ((strval($String)=="") and ($nullable==FALSE))
		$resultat="0";
	Else
		$resultat=$String;
    return $resultat;
}

function SqlBool($String,$nullable)
{
	if ((strval($String)=="") and ($nullable==TRUE))
		$resultat="null";
	elseif ((strval($String)!="Y") and ($nullable==FALSE))
		$resultat="FALSE";
	Else
		$resultat="TRUE";
    return $resultat;
}

/*********************************************************************/
//transforme : nom<mail>  en : $result->name et $result->mail
function MkAddressName($str)
{
	$r=new stdclass();
	$r->NAME="";
	$r->EMAIL="";
	$pos1 = strpos($str, "<");
	$pos2 = strpos($str, ">");
	//pas de séparateur <>
	if (($pos1===FALSE) and ($pos2===FALSE))
		$r->EMAIL=$str;
	else
	{
	   $r->NAME=substr($str,0,$pos1);
	   $r->EMAIL=substr($str,($pos1+1),strlen($str)-$pos1-2);
	}
	$pos3=strpos($str, "@");
	if (($r->NAME=="") and ($pos3!==FALSE)) $r->NAME=substr($str,0,$pos3);
	return $r;
}

/*********************************************************************/
class db
{
	function __construct($dbfile)
	{
		$this->CR="0";
		$this->MSG="";
		$ok=FALSE;
		foreach(PDO::getAvailableDrivers() as $driver) 
		{
			 $ok=($ok or (strtoupper($driver)=="SQLITE"));
		}  
		if (!$ok)
	    {
		   $this->CR="-1";
  		   $this->MSG="Extension PDO-SQLITE manquante";
		   return;
		}		
		try
		{
			$this->db = new PDO("sqlite:".$dbfile);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES , FALSE);
		}
		catch (Exception $e)
		{
			$this->CR="-1";
			$this->MSG=$e->getMessage();
			return;
		}
	}
	
	function Close()
	{
		if ($this->db) 
		{
			$inttrans=$this->db->inTransaction();
			if ($inttrans==1) $this->db->commit();
			$this->db=NULL;
		}
	}

	function execute_query($stmt)
	{
		$err="";
		try { $st = $this->db->prepare($stmt);		}
		catch(PDOException $e)  {  $err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage();}	
		if ($err=="")
		{
			try {	$st->execute();   }
			catch(PDOException $e)  
			{
				$err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage(); 
				return $err;
			}	
		}
		if ($err!="") return $err;
		return $st;
	}	
	function check_user($username,$pass)
	{
		$result=new stdclass();
		$result->CR="1";
		$result->MSG="";
		$sql="SELECT CPARAM, VPARAM FROM TBPARAM WHERE CPARAM IN ('USERNAME','PASSWORD');";
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$data=array();
		while ($row = $sth->fetchObject())
		{
			$data[]=$row;
		}
		$sth->closeCursor(); 
		$sth=NULL;

		$usr=False;
		$pas=False;
		foreach($data as $row)
		{
			if (($row->CPARAM=="USERNAME") and ($row->VPARAM==$username))  $usr=True;
			elseif (($row->CPARAM=="PASSWORD") and ($row->VPARAM==$pass)) $pas=True;
		}
		if ($usr===False)
			{
				$result->CR="-2";
				$result->MSG="Identifiant inconnu";
			}
		elseif ($pas===False)
			{
				$result->CR="-3";
				$result->MSG="mot de passe incorrect";
			}
		else $result->CR="0";
		return $result;
	}


	function get_mail_status($idmail)
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->SENT="";
		$sql="SELECT SEND FROM TBMAIL WHERE IDMAIL=".SqlInteger($idmail,FALSE).";";
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		if ($row = $sth->fetchObject()) $result->SENT=(intval($row->SEND)>0?"Y":"N");
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}

	function create_mail($mail_obj)
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->DATA="";
		$mail_obj->createts=date("Y-m-d H:i:s");
		$sql="INSERT INTO TBMAIL (SMTPSERVER,SMTPPORT,SMTPLOGIN,SMTPPASS,SMTPAUTH,SMTPSECURE,WITHAR,REPLYTO,SENDER,SUBJECT,BODY,CREATETS) VALUES ( ";
		$sql.=SqlString($mail_obj->smtpserver,FALSE).",";
		$sql.=SqlInteger($mail_obj->smtpport,FALSE).",";
		$sql.=SqlString($mail_obj->smtplogin,FALSE).",";
		$sql.=SqlString($mail_obj->smtppass,FALSE).",";
		$sql.=SqlBool($mail_obj->smtpauth,FALSE).",";
		$sql.=SqlString($mail_obj->smtpsecure,FALSE).",";
		$sql.=SqlBool($mail_obj->withar,FALSE).",";
		$sql.=SqlString($mail_obj->replyto,FALSE).",";
		$sql.=SqlString($mail_obj->from,FALSE).",";
		$sql.=SqlString($mail_obj->subject,FALSE).",";
		$sql.=SqlString($mail_obj->body,FALSE).",";
		$sql.=SqlString($mail_obj->createts,FALSE)." ";
		$sql.=");";
		
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
		
		$sql="SELECT MAX(IDMAIL) AS IDMAIL FROM TBMAIL;";
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			$this->delete_mail($mail_obj->createts);
			return $result;
		}
		if ($row = $sth->fetchObject()) $result->DATA=$row->IDMAIL;
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}
	
	function delete_mail($createts)
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->DATA="";
		$sql="DELETE FROM TBMAIL WHERE SEND=FALSE AND CREATETS=".SqlString($createts,FALSE);
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}


	function remove_dest($idmail,$typedest="CC")
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->DATA="";
		$sql="DELETE FROM TBMAILDEST WHERE IDMAIL=".SqlInteger($idmail,FALSE)." AND TYPEDEST=".SqlString($typedest,TRUE).";";
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}

	function put_dest($mail_obj,$typedest="CC")
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->DATA="";
		$result=$this->remove_dest($mail_obj->idmail,$typedest);
		if ($result->CR!="0") return $result;
		
		if ($typedest=="CC") $tbdest=$mail_obj->cctb;
		elseif ($typedest=="BCC") $tbdest=$mail_obj->bcctb;
		$sql="";		
		$sql.="INSERT INTO TBMAILDEST (IDMAIL,TYPEDEST,DEST) VALUES ";
		for($i=0;$i<count($tbdest);$i++)
		{
			 $sql.="( ".SqlString($mail_obj->idmail,FALSE).",".SqlString($typedest,TRUE).",".SqlString($tbdest[$i],FALSE).") ";
			 if ($i<count($tbdest)-1) $sql.=", ";
			 else $sql.=";";
		}
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}

	function add_file($idmail,$filename)
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$result->DATA="";
		
		if (!file_exists($filename)) 
		{
			$result->CR="-3";
			$result->MSG="File ".$filename." not found";
			return $result;
		}
		$sql="INSERT INTO TBMAILATTACH (IDMAIL,FILENAME,FILEDATA) VALUES (:IDMAIL,:FILENAME,:FILEDATA);";
		try { $st = $this->db->prepare($sql);		}
		catch(PDOException $e)  
		{  
			$result->CR="-1";
			$result->MSG="ERREUR PDO dans " . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage(); 
			return $result;
		}	
		$filedata = file_get_contents($filename);	
		$basen=basename($filename);
        $st->bindParam(':IDMAIL', $idmail,PDO::PARAM_INT);
		$st->bindParam(':FILENAME', $basen, PDO::PARAM_STR);
        $st->bindParam(':FILEDATA', $filedata, PDO::PARAM_LOB);
		try {	$st->execute();   }
		catch(PDOException $e)  
		{
			$result->CR="-1";
			$result->MSG="ERREUR PDO dans " . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage(); 
			return $result;
		}
		$st->closeCursor(); 
		$sth=NULL;
		return $result;
	}
	
	function update_mail($idmail)
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		$sql="UPDATE TBMAIL SET SEND=1, SENDTS=CURRENT_TIMESTAMP WHERE IDMAIL=".SqlInteger($idmail,FALSE);
		$sth = $this->execute_query($sql);
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
		return $result;
	}
	

}



class command
{
	function __construct()
	{
		$this->username=GetVariableFrom($_POST,"username","");
		$this->password=GetVariableFrom($_POST,"password","");
		$this->smtpserver=GetVariableFrom($_POST,"smtpserver","");
		$this->smtpport=GetVariableFrom($_POST,"smtpport","");
		$this->smtpsecure=GetVariableFrom($_POST,"smtpsecure","");
		$this->smtpauth=GetVariableFrom($_POST,"smtpauth","");
		$this->smtplogin=GetVariableFrom($_POST,"smtplogin","");
		$this->smtppass=GetVariableFrom($_POST,"smtppass","");
		$this->withar=GetVariableFrom($_POST,"withar","N");
		$this->replyto=GetVariableFrom($_POST,"replyto","");
		$this->from=GetVariableFrom($_POST,"from","");
		$this->subject=GetVariableFrom($_POST,"subject","");
		$this->body=GetVariableFrom($_POST,"body","");
		$this->action=GetVariableFrom($_POST,"action","");
		$this->idmail=GetVariableFrom($_POST,"idmail","");
		$this->cclist=GetVariableFrom($_POST,"cclist","");
		$this->bcclist=GetVariableFrom($_POST,"bcclist","");
		$this->fileattachname="";
		if (isset($_FILES['userfile']['name'])) 
		{
			$this->fileattachname = "./tmp/" . basename($_FILES['userfile']['name']);
			if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $this->fileattachname)) $this->fileattachname="";
		}
		$this->filenamelist=array();
	}

	function checkinit()
	{
		$this->cctb=filterlist($this->cclist);
		$this->bcctb=filterlist($this->bcclist);
		if (($this->username=="") or ($this->password=="")) return 403;
		elseif (($this->action!="new") and ($this->action!="putcc") and ($this->action!="putbcc") and ($this->action!="addfile") and ($this->action!="send")) return 1402;
		elseif (($this->action!="new") and (!is_numeric($this->idmail))) return 2402;
		elseif (($this->action=="new") and (!is_numeric($this->smtpport))) return 3402;
		elseif (($this->action=="new") and ($this->smtpserver=="")) return 4402;
		elseif (($this->action=="new") and ($this->smtplogin=="")) return 5402;
		elseif (($this->action=="new") and ($this->smtppass=="")) return 6402;
		elseif (($this->action=="new") and (filter_var($this->from, FILTER_VALIDATE_EMAIL))===False) return 7402;
		elseif (($this->action=="new") and (filter_var($this->replyto, FILTER_VALIDATE_EMAIL))===False) return 7402;
		elseif (($this->action=="new") and ($this->subject=="")) return 1405;
		elseif (($this->action=="new") and ($this->body=="")) return 1405;
		elseif (($this->action=="putcc") and (count($this->cctb)<=0)) return 1406;
		elseif (($this->action=="putbcc") and (count($this->bcctb)<=0)) return 2406;
		elseif (($this->action=="addfile") and ($this->fileattachname=="")) return 1407;
		elseif (($this->action=="addfile") and (!file_exists($this->fileattachname))) return 1408;
		elseif (($this->action=="addfile") and (filesize($this->fileattachname)<=0)) return 1408;
		elseif (($this->action=="send") and (!is_numeric($this->idmail))) return 1409;
		return 200;
	}
	
	function envoie_mail_secure($withar="N")
	{
		$result=new stdclass();
		$result->CR="0";
		$result->MSG="";
		if (count($listdestinataire)==0) 
		{
			$result->CR=-1;
			$result->MSG="Il manque un destinataire au mail";
			return $result;
		}
		$mail = new PHPMailer(true);
		$mail->CharSet = 'utf-8';
		$mail->isSMTP();
		$mail->SMTPDebug  = 0;
		$mail->Host       = $this->smtpserver;	
		$mail->Port       = $this->smtpport;
		$mail->SMTPAuth   = ((strtoupper($this->smtpauth)=="Y") and ($this->smtplogin!="") and ($this->smtppass!=""));
		$mail->SMTPSecure = ($mail->SMTPAuth===TRUE?$this->smtpsecure:"");
		$mail->Username   = $this->smtplogin;
		$mail->Password   = $this->smtppass;
		//Emetteur
		$f=MkAddressName($this->from);
		$mail->setFrom($f->EMAIL, $f->NAME);
		$f=MkAddressName($this->replyto);
		$mail->addReplyTo($f->EMAIL, $f->NAME);
		//Accusé réception :
		if ($withar!="N") $mail->ConfirmReadingTo = $f->EMAIL;
		foreach($this->bcclist as $dest)
		{
			$d=MkAddressName($dest);
			$mail->addBCC($d->EMAIL, $d->NAME);
		}
		foreach($this->cclist as $dest)
		{
			$d=MkAddressName($dest);
			$mail->addAddress($d->EMAIL, $d->NAME);
		}
		$mail->Subject  = $this->subject;
		$i=strripos($this->body,"<body>");
		$mailversion="TEXTE";
		if ($i===FALSE)
			$this->body="<body>".nl2br($this->body)."</body>";
		else $mailversion="HTML";
		$mail->msgHTML($this->body, dirname(__FILE__), true); //Create message bodies and embed images
		
		// on attache les fichiers au mail 
		foreach ($this->filenamelist as $filename) $mail->addAttachment($this->filename, basename ($this->filename));
		$ret=TRUE;
		$ret=@$mail->send();
		if ($ret!=TRUE) 
		{
			$result->CR=-1;
			$result->MSG="Erreur : lors de l'envoie du mail Veuillez réessayer plus tard ou contacter directement votre structure";
			return $result;
		}		
		return $result;
	}
	
}
/****************************************************************************/
$cmd=new command();
$r=$cmd->checkinit();
if ($r!=200) { echo($r); http_response_code($r%1000); die(); }
/****************************************************************************/
$data=new db("./db/mailapi.db");
$r=$data->check_user($cmd->username,$cmd->password);
if ($r->CR!="0") { echo($r->MSG); http_response_code(401); die(); }
/****************************************************************************/
if ($cmd->action=="new")
{
	$r=$data->create_mail($cmd);
	var_dump($r);
	if ($r->CR!="0") { echo($r->MSG); http_response_code(402); die(); }
	$cmd->idmail=$r->DATA;
}
/****************************************************************************/
$r=$data->get_mail_status($cmd->idmail);
if ($r->CR!="0") { echo($r->MSG); http_response_code(405); die(); }
if ($r->SENT=="Y") { echo("Message déjà envoyé"); http_response_code(405); die(); }
elseif ($r->SENT=="") { echo("Le message num ".$cmd->idmail." n'existe pas"); http_response_code(405); die(); }
/****************************************************************************/
if ((in_array($cmd->action,array("new","putcc"))) and (count($cmd->cctb)>0))
{
	$r=$data->put_dest($cmd,"CC");
	if ($r->CR!="0") { echo($r->MSG); http_response_code(405); die(); }
}
if ((in_array($cmd->action,array("new","putbcc"))) and (count($cmd->bcctb)>0))
{
	$r=$data->put_dest($cmd,"BCC");
	if ($r->CR!="0") { echo($r->MSG); http_response_code(405); die(); }
}
/****************************************************************************/
if ($cmd->action=="addfile")
{
	$r=$data->add_file($cmd->idmail,$cmd->fileattachname);
	if ($r->CR!="0") { echo($r->MSG); http_response_code(406); die(); }
}
/****************************************************************************/
if ($cmd->action=="send")
{
	//A faire : extraire toutes les fichiers joints, et mettre les chemins des fichiers dans $cmd->filenamelist
	//puis appeler envoie_mail_secure
	echo("Envoie du mail avec l'id ".$cmd->idmail);
	$r=$data->update_mail($cmd->idmail);
	if ($r->CR!="0") { echo($r->MSG); http_response_code(406); die(); }
}


$data->close();


?>
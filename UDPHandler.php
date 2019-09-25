<?php
//require '../start.php';
//$post = $postsManager->getPostById(8);
//https://stackoverflow.com/questions/4356289/php-random-string-generator

//EVENT LOGGING TYPE
class EvLoggingType
{
	const NONE = 0;
	const TERMINAL = 1;
	const FILE = 2;
}
//EVENT LOGGING FREQUENCY
class EvLoggingFrequency
{
	const NONE = 0;
	const THREEPERHOUR = 1;
	const ONCEPERHOUR = 2;
	const ONCEADAY = 3;
}
class TerminalDebug
{
	const FALSE = 0;
	const TRUE = 1;
}
//RUNTIME CONSTANTS
class Constants
{
	//ms

	//const TRANSACTION_EXPTIME = 180000;
	const TRANSACTION_EXPTIME = 5000; //pokud se to nevybavilo do 5s
	const LOOP_TICK = 300;
	const PAYLOAD_RCVDTIME = 400;
	const SESSION_TIMEOUT = 1800000;
}
//LOGIN RESPONSES
class Login
{
	const USERDOESNOTEXIST = 1;
	const WRONGCREDENTIALS = 2;

	public static function getTypeById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$auth = array_flip($class->getConstants());
			return $auth[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}

}
//PROTOCOL FLOW ENUMERATIONS
abstract class Action
{
	const REQ = 0;
	const DATA = 1;
	const FDATA = 11;
	const APV = 2;

	const ACK = 100;
	const FIN = 101;
	const RESEND = 102;
	const ERR = 103;

	const LOGIN = 200;
	const SUCC = 201;
	const UNFOUND = 202;
	const WRONGCRED = 203;
	const NOTLOGED = 204;
	const EXPIRED = 205;
	const UNDEF = 206;
	const INVTKN = 210;

	public static function getTypeById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$type = array_flip($class->getConstants());
			return $type[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}
}

abstract class Content
{
	const AKT = 0;
	const ZAV = 1;
	const USR = 2;
	const NAUSR = 3;
	const CLUB = 4;

	const PAIRINGRZ = 20;
	const PAIRINGPZ = 21;

	const POSITIONS = 30;

	const CLUBFRIENDS = 40;
	const CLUBFRIENDSFORCUP = 41;
	const MEFORTHECUP = 42;

	public static function getContentById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$content = array_flip($class->getConstants());
			return $content[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}
}

abstract class Quantity
{
	const LISTING = 0;
	const SINGLE = 1;

	public static function getQuantityById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$quantity = array_flip($class->getConstants());
			return $quantity[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}
}

abstract class Handle
{
	const CREATE = 0;
	const UPDATE = 1;

	const ASC = 100;
	const DESC = 101;
	//const FETCH = 2;

	public static function getHandleById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$handle = array_flip($class->getConstants());
			return $handle[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}
}

abstract class NA
{
	const NA = 255;

	public static function getTypeById($value)
	{
		try {
			$class = new ReflectionClass(__CLASS__);
			$NA = array_flip($class->getConstants());
			return $NA[$value];
		} catch (ReflectionException $ex) {
			throw new RuntimeException();
		}
	}
}

class UDPHandler
{
	//Hold socket and Managers
	private $socket = null;
	private $postsManager = null;
	private $usersManager = null;
	private $cupsManager = null;
	private $positionsManager = null;
	private $clubsManager = null;

	//encryption
	public $EncryptionKeyDefault = null;
	public $EncryptionIVDefault = null;


	//Constructor
	public function __construct($s, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager)
	{
		//TBD rewrite
		//construct the connection and shit inside
		$this->socket = $s;
		$this->postsManager = $postsManager;
		$this->usersManager = $usersManager;
		$this->cupsManager = $cupsManager;
		$this->positionsManager = $positionsManager;
		$this->clubsManager = $clubsManager;
		//Default hardcoded Key and IV, could be handshaked in the future
		//$this->EncryptionKeyDefault = "M4GHwHt3xtraDlu0RvhOhIc8JZvV/wpJZiDOR4I/sBQ=";
		//$this->EncryptionKeyDefault = "5SUUYId9Q5KWOlNnf1Tq9ARsbb92MgZ9";
		$this->EncryptionKeyDefault = pack('H*', "622C8CFDFDB336AE469159A250C27CA9500268F681068E40251AA4207F5C81DE");
		//$this->EncryptionIVDefault = "vnzCI6yuUU8JBfUiJrtyORM2AUfASp5xbdND+b2NZwQ=";
		//$this->EncryptionIVDefault = "YFPHmkIFZgH893UQiTp9pb8HTuP6IMkJ";
		$this->EncryptionIVDefault = pack('H*', "B4B69F6A3EBED580A2208BDB30EEE237FF5054B2B53CF8D1CC0451F45B21F367");

	}
	//Bind function
	public function BindOn($url, $port)
	{
		if (socket_bind($this->socket, $url, $port)) {
			//echo 'Server successful bound on ' . $url . ':' . $port . "\r\n";
			return true;
		} else {
			//echo 'Bind error';
			return false;
		}
	}

	////DATAGRAM IDENTIFICATION
	/// 0 Action
	public function DesiredAction($datagram)
	{
		//REWRITE to just return 0th byte?
		$action = ord($datagram[0]);
		echo "SYS ACTION IDENTIFICATION FUNCTION SAYS ".$action."\r\n";
		switch ($action) {
			case Action::REQ:
				return Action::REQ;
				break;
			case Action::DATA:
				return Action::DATA;
				break;
			case Action::FDATA:
				return Action::FDATA;
				break;
			case Action::APV:
				return Action::APV;
				break;
			case Action::ACK:
				return Action::ACK;
				break;
			case Action::FIN:
				return Action::FIN;
				break;
			case Action::RESEND:
				return Action::RESEND;
				break;
			case Action::ERR:
				return Action::ERR;
				break;
			case Action::LOGIN:
				return Action::LOGIN;
				break;
			case Action::SUCC:
				return Action::SUCC;
				break;
			case Action::UNFOUND:
				return Action::UNFOUND;
				break;
			case Action::WRONGCRED:
				return Action::WRONGCRED;
				break;
			case Action::NOTLOGED:
				return Action::NOTLOGED;
				break;
			case Action::EXPIRED:
				return Action::EXPIRED;
				break;
			case Action::UNDEF:
				return Action::UNDEF;
				break;
			case Action::INVTKN:
				return Action::INVTKN;
				break;
			default:
				return -1;
		}
		//echo "first byte: ".gettype(ord($payload[0]))." TYPE::REQ".gettype(Type::REQ)." TYPE::STORE".gettype(Type::STORE)."\r\n";
		/*
		if((ord($payload[0]))==(Action::REQ)){
			return Action::REQ;
		} else if ((ord($payload[0]))==(Action::STORE)){
			return Action::STORE;
		} else{
			return -1;
		}
		*/
		/*if(ord($payload[0])==(int)Type::REQ){
			return Type::REQ;
		} else if (ord($payload[1])==(int)Type::STORE){
			return Type::STORE;
		} else {
			return -1;
		}*/
		//return ord($payload[0]);
	}
	/// 1 Content
	public function DesiredContent($datagram)
	{
		$content = ord($datagram[1]);
		switch ($content) {
			case Content::AKT:
				return Content::AKT;
				break;
			case Content::ZAV:
				return Content::ZAV;
				break;
			case Content::USR:
				return Content::USR;
				break;
			case Content::NAUSR:
				return Content::NAUSR;
				break;
			case Content::CLUB:
				return Content::CLUB;
				break;
			case Content::PAIRINGRZ:
				return Content::PAIRINGRZ;
				break;
			case Content::PAIRINGPZ:
				return Content::PAIRINGPZ;
				break;
			case Content::POSITIONS:
				return Content::POSITIONS;
				break;
			case Content::CLUBFRIENDS:
				return Content::CLUBFRIENDS;
				break;
			case Content::CLUBFRIENDSFORCUP:
				return Content::CLUBFRIENDSFORCUP;
				break;
			case Content::MEFORTHECUP:
				return Content::MEFORTHECUP;
				break;
			default:
				return -1;
		}
		/*
		if ((ord($payload[1])) == (Content::AKT)) {
			return Content::AKT;
		} else if ((ord($payload[1])) == (Content::ZAV)) {
			return Content::ZAV;
		} else if ((ord($payload[1])) == (Content::USR)) {
			return Content::USR;
		} else if ((ord($payload[1])) == (Content::NAUSR)) {
			return Content::NAUSR;
		} else {
			return -1;
		}
		*/
	}
	/// 2 Quantity
	public function DesiredQuantity($datagram)
	{
		$quantity = ord($datagram[2]);
		switch ($quantity) {
			case Quantity::LISTING:
				return Quantity::LISTING;
				break;
			case Quantity::SINGLE:
				return Quantity::SINGLE;
				break;
			default:
				return -1;
		}
		//echo "from inside"+ord($payload[2]);
		/*
		if ((ord($payload[2])) == (Quantity::LISTING)) {
			return Quantity::LISTING;
		} else if ((ord($payload[2])) == (Quantity::SINGLE)) {
			return Quantity::SINGLE;
		} else {
			return -1;
		}
		*/
	}
	/// 3 Handle
	public function DesiredHandle($datagram)
	{
		$handle = (ord($datagram[3]));
		switch ($handle) {
			case Handle::CREATE:
				return Handle::CREATE;
				break;
			case Handle::UPDATE:
				return Handle::UPDATE;
				break;
			case Handle::ASC:
				return Handle::ASC;
				break;
			case Handle::DESC:
				return Handle::DESC;
				break;
			default:
				return -1;
		}
		//return $handle;
	}
	/// 4,5,6,7 optID
	public function DesiredID($datagram)
	{
		$fourth = (ord($datagram[4]));
		$third = (ord($datagram[5]));
		$second = (ord($datagram[6]));
		$first = (ord($datagram[7]));
		$_id = ($fourth*16777216)+($third*65536)+($second * 256) + ($first * 1);
		//echo "mezivypocet id: ".$id;
		if ($_id == 0) {
			return null;
		} else {
			return $_id;
		}
	}
	/// 8,9,10,11 Nth
	public function DesiredPayloadNumber($datagram)
	{
		$fourth = (ord($datagram[8]));
		$third = (ord($datagram[9]));
		$second = (ord($datagram[10]));
		$first = (ord($datagram[11]));
		$_number = ($fourth*16777216)+($third*65536)+($second*256) + ($first*1);
		return $_number;
	}
	/// 12,13,14,15 OutOfN
	public function DesiredPayloadTotal($datagram)
	{
		$fourth = (ord($datagram[12]));
		$third = (ord($datagram[13]));
		$second = (ord($datagram[14]));
		$first = (ord($datagram[15]));
		$_number = ($fourth*16777216)+($third*65536)+($second*256) + ($first*1);
		return $_number;
	}
	/// 16,17,18,19 Resend ID
	public function DesiredResend($datagram)
	{
		$fourth = (ord($datagram[16]));
		$third = (ord($datagram[17]));
		$second = (ord($datagram[18]));
		$first = (ord($datagram[19]));
		$_id = ($fourth*16777216)+($third*65536)+($second*256) + ($first*1);
		return $_id;
	}
	/// 16,17,18,19 Approve User ID
	public function DesiredApproveUser($datagram)
	{
		$fourth = (ord($datagram[16]));
		$third = (ord($datagram[17]));
		$second = (ord($datagram[18]));
		$first = (ord($datagram[19]));
		$_id = ($fourth*16777216)+($third*65536)+($second*256) + ($first*1);
		return $_id;
	}
	/// Slice head and content
	public function DesiredPayloadContent($datagram)
	{
		//print_r($datagram);
		$dg = unpack('C*', $datagram);
		//print_r($dg);
		$dg_truncated = array_slice($dg,16);
		//print_r(array_filter($dg_truncated));
		$_content = implode(array_map("chr", array_filter($dg_truncated)));
		echo "PAYLOAD CONTENT DUMP: ".$_content."\r\n";
		//$content = array_slice($datagram, 16);
		return $_content;
	}
	/// Slice head and content w/ offset for auth token
	public function DesiredPayloadContentOffset($datagram, $offset)
	{
		//print_r($datagram);
		$dg = unpack('C*', $datagram);
		//print_r($dg);
		$dg_truncated = array_slice($dg,(16+$offset));
		//print_r(array_filter($dg_truncated));
		$_content = implode(array_map("chr", array_filter($dg_truncated)));
		echo "PAYLOAD CONTENT DUMP: ".$_content."\r\n";
		//$content = array_slice($datagram, 16);
		return $_content;
	}
	//check if offset works
	public function UserAuthorizationToken($datagram, $offset=0)
	{
		$auth_tkn_json = $this->DesiredPayloadContentOffset($datagram, $offset);
		$auth_tkn_aa = json_decode($auth_tkn_json, true);
		echo "Callstack: UserAuthorizationToken()\r\n";
		//echo $auth_tkn_json."\r\n";
		//echo var_dump($auth_tkn_aa);
		//echo $auth_tkn_aa["auth_tkn"]."\r\n";
		echo "← returning ".$auth_tkn_aa["auth_tkn"].".\r\n";
		return ($auth_tkn_aa["auth_tkn"]);
	}

	//TODO maybe deprecate this
	/** input array, return null or 0-N */
	private function get_first($arr)
	{
		foreach ($arr as $k => $v) {
			return $v;
		}
		return null;
	}

	/*PROTOCOL FLOW*/
	public function MissingPayloadParts($openedData)
	{
		$listOfMissing = array();
		$auxList = array();
		$var = $openedData;
		//nemusi byt 1
		$N = $var->payloadCNT;
		echo "SYS PayloadCNT " . $N . "\r\n";
		//we have payload parts

		//make list of parts that we are supposed to have
		for ($i = 1; $i <= $N; $i++) {
			$auxList[$i] = false;
		}
		//fill out the parts we have in our object
		foreach ($var->payload as $key => $part) {
			$auxList[$key] = true;
		}
		foreach ($auxList as $key => $status) {
			if ($status == false) {
				array_push($listOfMissing, $key);
			}
		}
		echo "SYS sizeOfMissing".count($listOfMissing)."\r\n";
		return $listOfMissing;

	}

	////Communication functions
	///
	//100 ACK datagrams
	public function Acknowledge($s, $rip, $rport)
	{
		// <editor-fold defaultstate="collapsed" desc="ACK header">
		$_ack[0] = Action::ACK;       // 100 ACK
		$_ack[1] = NA::NA;     //<--FREE
		$_ack[2] = NA::NA;     //<--FREE
		$_ack[3] = NA::NA;     //<--FREE
		$_ack[4] = NA::NA;     //<--FREE
		$_ack[5] = NA::NA;     //<--FREE
		$_ack[6] = NA::NA;     //<--FREE
		$_ack[7] = NA::NA;     //<--FREE
		$_ack[8] = NA::NA;     //<--FREE
		$_ack[9] = NA::NA;     //<--FREE
		$_ack[10] = NA::NA;    //<--FREE
		$_ack[11] = NA::NA;    //<--FREE
		$_ack[12] = NA::NA;    //<--FREE
		$_ack[13] = NA::NA;    //<--FREE
		$_ack[14] = NA::NA;    //<--FREE
		$_ack[15] = NA::NA;    //<--FREE
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="ACK null body">
		for($i=16;$i<=415;$i++){
			$_ack[$i] = NA::NA;
		}
		// </editor-fold>
		//Send ACK
		//socket_sendto($s, implode(array_map("chr", $_ack)), 416, 0, $rip, $rport); //ack back
		$_ackEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_ack)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		socket_sendto($this->socket, $_ackEncrypted, 416, 0, $rip, $rport);
	}
	public function TripleACK($s, $rip, $rport)
	{
		echo "TRIPLE ACK\r\n";
		$this->Acknowledge($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Acknowledge($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Acknowledge($s, $rip, $rport);
	}

	//101 FIN datagrams
	public function Finalize($s, $rip, $rport)
	{
		// <editor-fold defaultstate="collapsed" desc="FIN header">
		$_fin[0] = Action::FIN;       // 101 FIN
		$_fin[1] = NA::NA;     //<--FREE
		$_fin[2] = NA::NA;     //<--FREE
		$_fin[3] = NA::NA;     //<--FREE
		$_fin[4] = NA::NA;     //<--FREE
		$_fin[5] = NA::NA;     //<--FREE
		$_fin[6] = NA::NA;     //<--FREE
		$_fin[7] = NA::NA;     //<--FREE
		$_fin[8] = NA::NA;     //<--FREE
		$_fin[9] = NA::NA;     //<--FREE
		$_fin[10] = NA::NA;    //<--FREE
		$_fin[11] = NA::NA;    //<--FREE
		$_fin[12] = NA::NA;    //<--FREE
		$_fin[13] = NA::NA;    //<--FREE
		$_fin[14] = NA::NA;    //<--FREE
		$_fin[15] = NA::NA;    //<--FREE
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="FIN null body">
		for($i=16;$i<=415;$i++){
			$_fin[$i] = NA::NA;
		}
		// </editor-fold>
		//Send FIN
		$_finEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_fin)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		//socket_sendto($s, implode(array_map("chr", $_fin)), 416, 0, $rip, $rport); //fin back
		socket_sendto($s, $_finEncrypted, 416, 0, $rip, $rport);
	}
	public function TripleFIN($s, $rip, $rport)
	{
		$this->Finalize($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Finalize($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Finalize($s, $rip, $rport);
	}

	//102 RESEND
	public function RequestMissingPart($id, $d_ip, $d_port)
	{
		//Decompose number
		$id16M = 0;
		$id65k = 0;
		$id256 = 0;
		$id1 = 0;
		$this->EncodeIntegerTo4B($id,$id16M,$id65k,$id256,$id1);

		//Header 0-15
		$_reqMissing[0] = Action::RESEND;
		for ($i = 1; $i <= 15; $i++) {
			$_reqMissing[$i] = NA::NA;
		}
		//content coding 16, 17, 18 and 19
		$_reqMissing[16] = chr($id16M);
		$_reqMissing[17] = chr($id65k);
		$_reqMissing[18] = chr($id256);
		$_reqMissing[19] = chr($id1);
		//null 20-415
		for ($i = 20; $i <= 415; $i++) {
			$_reqMissing[$i] = NA::NA;
		}

		//send, TODO ECRYPT KURWA
		//$string_datagram = implode($_reqMissing);
		$_reqMissingEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_reqMissing)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		socket_sendto($this->socket, $_reqMissingEncrypted, 416, 0, $d_ip, $d_port);
	}

	//103 ERR datagrams
	public function Error($s, $rip, $rport){
		// <editor-fold defaultstate="collapsed" desc="ERR header">
		$_err[0] = Action::ERR;       // 103 ERR
		$_err[1] = NA::NA;     //<--FREE
		$_err[2] = NA::NA;     //<--FREE
		$_err[3] = NA::NA;     //<--FREE
		$_err[4] = NA::NA;     //<--FREE
		$_err[5] = NA::NA;     //<--FREE
		$_err[6] = NA::NA;     //<--FREE
		$_err[7] = NA::NA;     //<--FREE
		$_err[8] = NA::NA;     //<--FREE
		$_err[9] = NA::NA;     //<--FREE
		$_err[10] = NA::NA;    //<--FREE
		$_err[11] = NA::NA;    //<--FREE
		$_err[12] = NA::NA;    //<--FREE
		$_err[13] = NA::NA;    //<--FREE
		$_err[14] = NA::NA;    //<--FREE
		$_err[15] = NA::NA;    //<--FREE
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="ERR null body">
		for($i=16;$i<=415;$i++){
			$_err[$i] = NA::NA;
		}
		// </editor-fold>
		//Send ERR
		$_errEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_err)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		//socket_sendto($s, implode(array_map("chr", $_err)), 416, 0, $rip, $rport); //err back
		socket_sendto($s, $_errEncrypted, 416, 0, $rip, $rport);
	}
	public function TripleERR($s, $rip, $rport)
	{
		$this->Error($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Error($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Error($s, $rip, $rport);
	}

	//201 SUCC send token datagrams
	public function Success($s, $rip, $rport, $token)
	{
		// <editor-fold defaultstate="collapsed" desc="DATA header">
		$_succ[0] = Action::SUCC;       // 0 SUCC
		$_succ[1] = NA::NA;     //<--FREE
		$_succ[2] = NA::NA;     //<--FREE
		$_succ[3] = NA::NA;     //<--FREE
		$_succ[4] = NA::NA;     //<--FREE
		$_succ[5] = NA::NA;     //<--FREE
		$_succ[6] = NA::NA;     //<--FREE
		$_succ[7] = NA::NA;     //<--FREE
		$_succ[8] = NA::NA;       //upper Nth
		$_succ[9] = NA::NA;       //lower Nth
		$_succ[10] = NA::NA;      //upper ofN
		$_succ[11] = NA::NA;      //lower ofN
		$_succ[12] = NA::NA;    //<--FREE
		$_succ[13] = NA::NA;    //<--FREE
		$_succ[14] = NA::NA;    //<--FREE
		$_succ[15] = NA::NA;    //<--FREE
		// </editor-fold>
		$tokenMsgByteArr = unpack('C*', $token);
		echo "delka tokenu jako arraje je ".count($tokenMsgByteArr)."\r\n";

		$sendTokenDatagram = array_merge($_succ, $tokenMsgByteArr);
		$sendTokenDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $sendTokenDatagram)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		echo "delka zakodovaneho datagramu je ".strlen($sendTokenDatagramEncrypted)."\r\n";

		//responsePayload<$ith, $subResponseDatagramEncrypted>
		//$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
		socket_sendto($s, $sendTokenDatagramEncrypted, 416, 0, $rip, $rport);
	}
	//WTF KURVA?!
	public function TripleSUCC($s, $rip, $rport, $token)
	{
		$this->Success($s, $rip, $rport, $token);
		//TODO IMPLEMENT random short wait
		$this->Success($s, $rip, $rport, $token);
		//TODO IMPLEMENT random short wait
		$this->Success($s, $rip, $rport, $token);
		//TODO IMPLEMENT random short wait
	}

	//202 UNFOUND datagrams
	public function Unfound($s, $rip, $rport)
	{
		// <editor-fold defaultstate="collapsed" desc="UNFOUND header">
		$_unfound[0] = Action::UNFOUND;       // 202 UNFOUND
		$_unfound[1] = NA::NA;     //<--FREE
		$_unfound[2] = NA::NA;     //<--FREE
		$_unfound[3] = NA::NA;     //<--FREE
		$_unfound[4] = NA::NA;     //<--FREE
		$_unfound[5] = NA::NA;     //<--FREE
		$_unfound[6] = NA::NA;     //<--FREE
		$_unfound[7] = NA::NA;     //<--FREE
		$_unfound[8] = NA::NA;     //<--FREE
		$_unfound[9] = NA::NA;     //<--FREE
		$_unfound[10] = NA::NA;    //<--FREE
		$_unfound[11] = NA::NA;    //<--FREE
		$_unfound[12] = NA::NA;    //<--FREE
		$_unfound[13] = NA::NA;    //<--FREE
		$_unfound[14] = NA::NA;    //<--FREE
		$_unfound[15] = NA::NA;    //<--FREE
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="UNFOUND null body">
		for($i=16;$i<=415;$i++){
			$_unfound[$i] = NA::NA;
		}
		// </editor-fold>
		//Send UNFOUND
		$_unfoundEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_unfound)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		socket_sendto($s, $_unfoundEncrypted, 416, 0, $rip, $rport);
	}
	public function TripleUNFOUND($s, $rip, $rport)
	{
		$this->Unfound($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Unfound($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
		$this->Unfound($s, $rip, $rport);
		//TODO IMPLEMENT random short wait
	}

	//203 WRONGCRED datagrams
	public function WrongCredentials($s, $rip, $rport)
	{
		// <editor-fold defaultstate="collapsed" desc="WRONGCRED header">
		$_wrongCred[0] = Action::WRONGCRED;       // 202 WRONGCRED
		$_wrongCred[1] = NA::NA;     //<--FREE
		$_wrongCred[2] = NA::NA;     //<--FREE
		$_wrongCred[3] = NA::NA;     //<--FREE
		$_wrongCred[4] = NA::NA;     //<--FREE
		$_wrongCred[5] = NA::NA;     //<--FREE
		$_wrongCred[6] = NA::NA;     //<--FREE
		$_wrongCred[7] = NA::NA;     //<--FREE
		$_wrongCred[8] = NA::NA;     //<--FREE
		$_wrongCred[9] = NA::NA;     //<--FREE
		$_wrongCred[10] = NA::NA;    //<--FREE
		$_wrongCred[11] = NA::NA;    //<--FREE
		$_wrongCred[12] = NA::NA;    //<--FREE
		$_wrongCred[13] = NA::NA;    //<--FREE
		$_wrongCred[14] = NA::NA;    //<--FREE
		$_wrongCred[15] = NA::NA;    //<--FREE
		// </editor-fold>
		// <editor-fold defaultstate="collapsed" desc="WRONGCRED null body">
		for($i=16;$i<=415;$i++){
			$_wrongCred[$i] = NA::NA;
		}
		// </editor-fold>
		//Send WRONGCRED
		$_wrongCredEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $_wrongCred)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
		socket_sendto($s, $_wrongCredEncrypted, 416, 0, $rip, $rport);
	}
	public function TripleWRONGCRED($s, $rip, $rport)
	{
		//ugh? TODO
	}
	//some functions here
	public function DecodeCredentials($payload)
	{
		$_ret_assoc = json_decode($payload, true);
		return $_ret_assoc;
	}

	public function Authenticate($username, $password)
	{
		$_token = $this->usersManager->loginFromXamarin($username, $password);
		return $_token;
	}
	//MAYBE DROP THIS
	/*
	public function Finish($s, $rip, $rport)
	{
		$response = array(Action::FIN);
		socket_sendto($s, implode(array_map("chr", $response)), 416, 0, $rip, $rport); //inform the guys on the other side
	}
	public function Error($s, $rip, $rport)
	{
		//uh does this work?
		$response = array(Action::ERR);
		socket_sendto($s, implode(array_map("chr", $response)), 416, 0, $rip, $rport); //inform the guys on the other side
	}
	public function Resend($s, $rip, $rport)
	{
		//where to put id?
	}
	*/

	/*ACTIONS*/ //DAFAQ TODO IMPLEMENT FUNCTIONS HERE AND CALL THEM FROM DECISION TREES OF OBJECTS
	public function SendArticle()
	{
		//ugh NOT NEEDED
	}

	///AUX conversion function
	//Encode Int32 to 4 bytes for transfer
	public function EncodeIntegerTo4B($number, &$n16M, &$n65k, &$n256, &$n1)
	{
		$auxiliary = $number;
		$n16M = floor($auxiliary/16777216); // 4th B, 2^24 ~ 16777216
		$auxiliary = ($auxiliary%16777216);
		$n65k = floor($auxiliary/65536); // 3rd B, 2^16 ~ 65536
		$auxiliary = ($auxiliary%65536);
		$n256 = floor($auxiliary/256); // 2nd B, 2^8 ~ 256
		$auxiliary = ($auxiliary%256);
		$n1 = floor($auxiliary/1); // 1st B, 2^0 ~ 1
	}

	//Decode 4 bytes from datagram to Int32
	public function Decode4BToInteger($n16M, $n65k, $n256, $n1)
	{
		$_result = ($n16M*16777216)+($n65k*65536)+($n256*256)+($n1*1);
		return $_result;
	}

	//static konstruktor session objektu na novem vlakne
}

abstract class Transaction
{
	public function Type()
	{
		return null;
	}
}

class OpenedReq extends Transaction
{
	//rezie
	public $socket;
	public $tokens;
	//managers
	public $postsManager = null;
	public $usersManager = null;
	public $cupsManager = null;
	public $positionsManager = null;
	public $clubsManager = null;
	//sender identification info
	public $IP;
	public $port;
	public $timestamp; //milisekundy
	//encryption
	public $EncryptionKeyDefault;
	public $EncryptionIVDefault;
	// Header info
	public $content;
	public $quantity;
	public $optID;
	public $canBeDeleted;

	//Totally fine lol, for resends
	public $responseDatagrams;


	//Returns type when asked
	public function Type()
	{
		return Action::REQ;
	}

	//Decide and prepare content to send and call CutEncryptSendBye on the content
	public function SatisfyReq()
	{
		echo "entering REQ\r\n";
		echo "content: ".$this->content."\r\n";
		//Satisfy on Content
		switch ($this->content) {
			case Content::AKT:
				//Satisfy AKT on Quantity
				switch ($this->quantity) {
					//Send Aktuality Listing
					case Quantity::LISTING:
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						//TODO read number from datagram
						$menu = array();
						$menu = $this->postsManager->getLastNPosts(2);
						//array of posts in json
						$json = "[";
						for ($i = 0; $i < count($menu); $i++) {
							if ($i != 0)
								$json .= ",";
							$p = $menu[$i];
							//echo $i.": ";
							//echo($p->Serialize());
							//echo "\r\n";
							$json .= $p->Serialize();

						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//socket_sendto($this->socket, implode(array_map("chr", $subResponseDatagramArrEncrypted)), 416, 0, $this->IP, $this->port);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//CMNTED
							//$responseLength = count($subResponseDatagramArr);
							//socket_sendto($this->socket, implode(array_map("chr", $subResponseDatagramArrEncrypted)), 416, 0, $this->IP, $this->port);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							//ZAREZ FIN
							//encode menu aktualit CSV
							//foreach(posts as post)
							//post.Serialize()
							//cut this and send it
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
					//Send Aktualita Single
					case Quantity::SINGLE:
						//BS delete
						////$Nth = 1;
						////$NthUpper = floor($Nth / 255);
						////$NthLower = ($Nth % 255);
						////$outOfN = 1;
						////$outOfNUpper = floor($outOfN / 255);
						////$outOfNLower = ($outOfN % 255);
						//echo $NthUpper."(255)".$NthLower."(1)/".$outOfNUpper."(255)".$outOfNLower."(1)\r\n";
						// TODO vykuchat CSV article
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>								 //400B payload
						//$p = $postsManager->getPost
						$p = $this->postsManager->getPostById($this->optID);
						$p_ser = $p->Serialize();
						/*
						$i = 1;
						$N = (floor(strlen($p_ser) / 400) + 1);
						while (strlen($p_ser) >= 400) {
							echo "WHILE\r\n";
							$subMsgString = substr($p_ser, 0, 400);
							$p_ser = substr($p_ser, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//socket_sendto($this->socket, implode(array_map("chr", $subResponseDatagramArr)), 416, 0, $this->IP, $this->port);
							//responsePayload<id, datagram>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($p_ser)!=0) {
							echo $i . "/" . $N . " " . strlen($p_ser) . " " . $p_ser . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $p_ser;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//responsePayload<id, datagram>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							//CMNTED
							//$responseLength = count($responseDatagramArr);
							//echo "odpoved ma".$responseLength;

							//socket_sendto($this->socket, implode(array_map("chr", $responseDatagramArr)), 416, 0, $this->IP, $this->port);
							//ZAREZ FIN

							$string_datagram = "sending article" . $this->optID . "\r\n";
							//socket_sendto($this->socket, $string_datagram, 416, 0, $this->IP, $this->port);

						}
						*/
						$this->CutEncryptSendBye($this, $header, $p_ser);
						break;
				}
				break;
			case Content::ZAV:
				//Satisty ZAV on Quantity
				switch ($this->quantity) {
					//Send ZAV Listing
					case Quantity::LISTING:
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						//TODO read number from datagram
						//$menu = array();
						//$menu = $this->postsManager->getLastNPosts(2);
						$cups = array();
						$cups = $this->cupsManager->findAllUpcomingCupsEarliestFirst();
						//array of posts in json
						$json = "[";
						for ($i = 0; $i < count($cups); $i++) {
							if ($i != 0)
								$json .= ",";
							$c = $cups[$i];

							$json .= $c->SerializeSlim();

						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
					//Send ZAV Single
					case Quantity::SINGLE:
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						//Three queries for three JSONs for this cup
						$Positions = $this->positionsManager->findAllPositions(); //Position(id, poz)
						$Users = $this->usersManager->findAllRegisteredUsersForTheCup($this->optID); //User(id, first_name, last_name, email, approved, rights, klubaffil)
						$Nametags = $this->usersManager->findAllNametagsForTheCup($this->optID); // Users... bs
						$PairsRP = $this->usersManager->findPairedPozIDUserIDOnCup($this->optID); //Pair(idpoz, iduser)

						$JSON1 = array();
						$JSON2 = array();
						$JSON3 = array();
						$JSON4 = array();

						$JSON1 = "[";
						for ($i = 0; $i < count($Positions); $i++) {
							if ($i != 0)
								$JSON1 .= ",";
							$_position = $Positions[$i];

							$JSON1 .= $_position->Serialize();

						}
						$JSON1 .= "]";
						$JSON2 = "[";
						for ($i = 0; $i < count($Users); $i++) {
							if ($i != 0)
								$JSON2 .= ",";
							$_user = $Users[$i];

							$JSON2 .= $_user->SerializeSlim();

						}
						$JSON2 .= "]";
						$JSON3 = "[";
						for ($i = 0; $i < count($Nametags); $i++) {
							if ($i != 0)
								$JSON3 .= ",";
							$_nametag = $Nametags[$i];

							$JSON3 .= $_nametag->SerializeSlim();

						}
						$JSON3 .= "]";
						$JSON4 = "[";
						for ($i = 0; $i < count($PairsRP); $i++) {
							if ($i != 0)
								$JSON4 .= ",";
							$_pair = $PairsRP[$i];

							$JSON4 .= $_pair->Serialize();

						}
						$JSON4 .= "]";
						$json = $JSON1.";".$JSON2.";".$JSON3.";".$JSON4;
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
						//encode vsechno okolo jednoho zavodu $optID CSV
					break;
				}
				break;
			case Content::NAUSR:
				//Send NAUSR
				switch ($this->quantity) {
					//Send NAUSR Listing
					case Quantity::LISTING:
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>
						$nausrs = array();
						$nausrs = $this->usersManager->findAllInactiveUsersOrderByLastNameDesc();
						//array of not approved users in json
						$json="[";
						for ($i = 0; $i < count($nausrs); $i++) {
							if ($i != 0)
								$json .= ",";
							$p = $nausrs[$i];
							//$User->SerializeSlim(); Slim jen 4
							$json .= $p->SerializeSlim();
						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						//Send data section
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//header part of payload section
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//responsePayload<id, datagram>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							//socket_sendto($this->socket, implode(array_map("chr", $subResponseDatagramArr)), 416, 0, $this->IP, $this->port);
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
							//responsePayload<id, datagram>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;

							//$responseLength = count($responseDatagramArr);
							//echo "odpoved ma".$responseLength;

							//socket_sendto($this->socket, implode(array_map("chr", $responseDatagramArr)), 416, 0, $this->IP, $this->port);
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);

							//Sent last data
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
					//N/A SINGLE NAUSR
					case Quantity::SINGLE:
						//no implementation
						break;
				}
				break;
			case Content::USR:
				//Send USR
				switch ($this->quantity) {
					case Quantity::LISTING:
						//list of all users CSV
						break;
					//N/A SINGLE USER
					case Quantity::SINGLE:
						//no implementation
						break;
				}
				break;
			case Content::CLUB:
				echo "retering REQ->CLUB\r\n";
				switch ($this->quantity){
					case Quantity::LISTING:
						echo"entering REQ->CLUB->LISTING\r\n";
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						//$menu = array();
						//$menu = $this->postsManager->getLastNPosts(2);
						$clubs = array();
						$clubs = $this->clubsManager->findAllClubs();
						//array of posts in json
						$json = "[";
						for ($i = 0; $i < count($clubs); $i++) {
							if ($i != 0)
								$json .= ",";
							$c = $clubs[$i];

							$json .= $c->SerializeSlim();

						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
					case Quantity::SINGLE:
						//nvm
						break;
				}
				break;
			case Content::POSITIONS:
				switch ($this->quantity){
					case Quantity::LISTING:
						switch ($this->handle){
							case Handle::DESC:
								// <editor-fold defaultstate="collapsed" desc="DATA header">
								$header[0] = Action::DATA;       // 0 DATA
								$header[1] = NA::NA;     //<--FREE
								$header[2] = NA::NA;     //<--FREE
								$header[3] = NA::NA;     //<--FREE
								$header[4] = NA::NA;     //<--FREE
								$header[5] = NA::NA;     //<--FREE
								$header[6] = NA::NA;     //<--FREE
								$header[7] = NA::NA;     //<--FREE
								$header[8] = NA::NA;       //upper Nth
								$header[9] = NA::NA;       //lower Nth
								$header[10] = NA::NA;      //upper ofN
								$header[11] = NA::NA;      //lower ofN
								$header[12] = NA::NA;    //<--FREE
								$header[13] = NA::NA;    //<--FREE
								$header[14] = NA::NA;    //<--FREE
								$header[15] = NA::NA;    //<--FREE
								// </editor-fold>

								//TODO read number from datagram
								//$menu = array();
								//$menu = $this->postsManager->getLastNPosts(2);
								$positions = array();
								$positions = $this->positionsManager->findAllPositions(); //Position(id, poz);
								//array of posts in json
								$json = "[";
								for ($i = 0; $i < count($positions); $i++) {
									if ($i != 0)
										$json .= ",";
									$c = $positions[$i];

									$json .= $c->SerializeSlim();

								}
								$json .= "]";
								echo $json . "\r\n";
								echo "EndLoop \r\n";
								/*
								$i = 1;
								$N = (floor(strlen($json) / 400) + 1);
								while (strlen($json) >= 400) {
									$subMsgString = substr($json, 0, 400);
									$json = substr($json, 400);
									echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
									$subMsgByteArr = unpack('C*', $subMsgString);
									//adjust header
									$iUpper = floor($i / 255);
									$iLower = ($i % 255);
									$NUpper = floor($N / 255);
									$NLower = ($N % 255);
									//--
									$header[8] = $iUpper;
									$header[9] = $iLower;
									$header[10] = $NUpper;
									$header[11] = $NLower;

									echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
									print_r($header);
									echo "\r\n";
									$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
									$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

									//responsePayload<$ith, $subResponseDatagramEncrypted>
									$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
									socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
									$i = $i + 1;
								}
								//resp |400|...|400|0 < THIS < 400|
								if(strlen($json)!=0) {
									echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
									//adjust header
									$iUpper = floor($i / 255);
									$iLower = ($i % 255);
									$NUpper = floor($N / 255);
									$NLower = ($N % 255);
									//--
									$header[8] = $iUpper;
									$header[9] = $iLower;
									$header[10] = $NUpper;
									$header[11] = $NLower;
									echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
									print_r($header);
									echo "\r\n";
									$msgString = $json;
									$msgByteArr = unpack('C*', $msgString);

									$subResponseDatagramArr = array_merge($header, $msgByteArr);
									$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

									//responsePayload<$ith, $subResponseDatagramEncrypted>
									$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
									socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
								}
								*/
								$this->CutEncryptSendBye($this, $header, $json);
								break;
							case Handle::ASC:
								//not useful
								break;
						}
						break;
					case Quantity::SINGLE:
						//not useful
						break;
				}
				break;
			case Content::CLUBFRIENDS:
				switch ($this->quantity){
					case Quantity::LISTING:
						echo"entering REQ->CLUBFRIENDS->LISTING\r\n";
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						$transKeyIpPort = $this->IP.":".$this->port;
						$affiliation = $this->tokens[$transKeyIpPort]->affiliation;

						$users = array();
						$users = $this->usersManager->findAllComrades($affiliation);
						//array of posts in json
						$json = "[";
						for ($i = 0; $i < count($users); $i++) {
							if ($i != 0)
								$json .= ",";
							$u = $users[$i];

							$json .= $u->SerializeSlim();

						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
				}
				break;
			case Content::CLUBFRIENDSFORCUP:
				switch ($this->quantity){
					case Quantity::LISTING:
						echo"entering REQ->CLUBFRIENDSFORCUP->LISTING\r\n";
						// <editor-fold defaultstate="collapsed" desc="DATA header">
						$header[0] = Action::DATA;       // 0 DATA
						$header[1] = NA::NA;     //<--FREE
						$header[2] = NA::NA;     //<--FREE
						$header[3] = NA::NA;     //<--FREE
						$header[4] = NA::NA;     //<--FREE
						$header[5] = NA::NA;     //<--FREE
						$header[6] = NA::NA;     //<--FREE
						$header[7] = NA::NA;     //<--FREE
						$header[8] = NA::NA;       //upper Nth
						$header[9] = NA::NA;       //lower Nth
						$header[10] = NA::NA;      //upper ofN
						$header[11] = NA::NA;      //lower ofN
						$header[12] = NA::NA;    //<--FREE
						$header[13] = NA::NA;    //<--FREE
						$header[14] = NA::NA;    //<--FREE
						$header[15] = NA::NA;    //<--FREE
						// </editor-fold>

						$transKeyIpPort = $this->IP.":".$this->port;
						$affiliation = $this->tokens[$transKeyIpPort]->affiliation;

						$cupID = $this->optID;

						$users = array();
						$users = $this->usersManager->findAllRegisteredComradesForTheCup($cupID, $affiliation);
						//array of posts in json
						$json = "[";
						for ($i = 0; $i < count($users); $i++) {
							if ($i != 0)
								$json .= ",";
							$u = $users[$i];

							$json .= $u->SerializeSlim();

						}
						$json .= "]";
						echo $json . "\r\n";
						echo "EndLoop \r\n";
						/*
						$i = 1;
						$N = (floor(strlen($json) / 400) + 1);
						while (strlen($json) >= 400) {
							$subMsgString = substr($json, 0, 400);
							$json = substr($json, 400);
							echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
							$subMsgByteArr = unpack('C*', $subMsgString);
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;

							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
							$i = $i + 1;
						}
						//resp |400|...|400|0 < THIS < 400|
						if(strlen($json)!=0) {
							echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
							//adjust header
							$iUpper = floor($i / 255);
							$iLower = ($i % 255);
							$NUpper = floor($N / 255);
							$NLower = ($N % 255);
							//--
							$header[8] = $iUpper;
							$header[9] = $iLower;
							$header[10] = $NUpper;
							$header[11] = $NLower;
							echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
							print_r($header);
							echo "\r\n";
							$msgString = $json;
							$msgByteArr = unpack('C*', $msgString);

							$subResponseDatagramArr = array_merge($header, $msgByteArr);
							$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);

							//responsePayload<$ith, $subResponseDatagramEncrypted>
							$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
							socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
						}
						*/
						$this->CutEncryptSendBye($this, $header, $json);
						break;
				}
				break;
			case Content::MEFORTHECUP:
				// reply to the answer whether i am registered for this cup

				// <editor-fold defaultstate="collapsed" desc="DATA header">
				$header[0] = Action::DATA;       // 0 DATA
				$header[1] = NA::NA;     //<--FREE
				$header[2] = NA::NA;     //<--FREE
				$header[3] = NA::NA;     //<--FREE
				$header[4] = NA::NA;     //<--FREE
				$header[5] = NA::NA;     //<--FREE
				$header[6] = NA::NA;     //<--FREE
				$header[7] = NA::NA;     //<--FREE
				$header[8] = NA::NA;       //upper Nth
				$header[9] = NA::NA;       //lower Nth
				$header[10] = NA::NA;      //upper ofN
				$header[11] = NA::NA;      //lower ofN
				$header[12] = NA::NA;    //<--FREE
				$header[13] = NA::NA;    //<--FREE
				$header[14] = NA::NA;    //<--FREE
				$header[15] = NA::NA;    //<--FREE
				// </editor-fold>

				//Action section
				$transKeyIpPort = $this->IP.":".$this->port;
				$userID = $this->tokens[$transKeyIpPort]->id;

				$cupID = $this->optID;

				$result = $this->cupsManager->isUserAvailableForTheCup($userID, $cupID);
				//End action section

				//Send data section
				$json = "";
				if($result==true)
				{
					$json="{\"answer\":\"true\"}";
				}
				else if ($result==false)
				{
					$json="{\"answer\":\"false\"}";
				}

				/*
				$i = 1;
				$N = (floor(strlen($json) / 400) + 1);
				while (strlen($json) >= 400) {
					$subMsgString = substr($json, 0, 400);
					$json = substr($json, 400);
					echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
					$subMsgByteArr = unpack('C*', $subMsgString);
					//adjust header
					$iUpper = floor($i / 255);
					$iLower = ($i % 255);
					$NUpper = floor($N / 255);
					$NLower = ($N % 255);
					//header part of payload section
					$header[8] = $iUpper;
					$header[9] = $iLower;
					$header[10] = $NUpper;
					$header[11] = $NLower;

					echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
					print_r($header);
					echo "\r\n";
					$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
					$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
					//responsePayload<id, datagram>
					$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;
					//socket_sendto($this->socket, implode(array_map("chr", $subResponseDatagramArr)), 416, 0, $this->IP, $this->port);
					socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
					$i = $i + 1;
				}
				//resp |400|...|400|0 < THIS < 400|
				if(strlen($json)!=0) {
					echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
					//adjust header
					$iUpper = floor($i / 255);
					$iLower = ($i % 255);
					$NUpper = floor($N / 255);
					$NLower = ($N % 255);
					//--
					$header[8] = $iUpper;
					$header[9] = $iLower;
					$header[10] = $NUpper;
					$header[11] = $NLower;
					echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
					print_r($header);
					echo "\r\n";
					$msgString = $json;
					$msgByteArr = unpack('C*', $msgString);

					$subResponseDatagramArr = array_merge($header, $msgByteArr);
					$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $this->EncryptionIVDefault);
					//responsePayload<id, datagram>
					$this->responseDatagrams[$i] = $subResponseDatagramEncrypted;

					//$responseLength = count($responseDatagramArr);
					//echo "odpoved ma".$responseLength;

					//socket_sendto($this->socket, implode(array_map("chr", $responseDatagramArr)), 416, 0, $this->IP, $this->port);
					socket_sendto($this->socket, $subResponseDatagramEncrypted, 416, 0, $this->IP, $this->port);
					//Sent last data
				}
				*/
				$this->CutEncryptSendBye($this, $header, $json);
				break;
		}
		//$payload $someManager->getSthSerialized($optId); //IMPLEMENT ON someManager
		//if (strlen($payload))>=400
		//sendto($IP, CSV $payload);
	}

	//Cut and send by parts payload (universal network function for sending)
	public function CutEncryptSendBye(&$OpenedReqDemanding, &$header, $json)
	{
		$i = 1;
		$N = (floor(strlen($json) / 400) + 1);
		while (strlen($json) >= 400) {
			$subMsgString = substr($json, 0, 400);
			$json = substr($json, 400);
			echo $i . "/" . $N . " " . strlen($subMsgString) . " " . $subMsgString . "\r\n";
			$subMsgByteArr = unpack('C*', $subMsgString);

			//Decompose number i
			$i16M = 0;
			$i65k = 0;
			$i256 = 0;
			$i1 = 0;
			$this->EncodeIntegerTo4B($i,$i16M,$i65k,$i256,$i1);

			//Decompose number N
			$N16M = 0;
			$N65k = 0;
			$N256 = 0;
			$N1 = 0;
			$this->EncodeIntegerTo4B($N,$N16M,$N65k,$N256,$N1);

			//adjust header
			//$iUpper = floor($i / 255);
			//$iLower = ($i % 255);
			//$NUpper = floor($N / 255);
			//$NLower = ($N % 255);
			//--
			$header[8] = $i16M;
			$header[9] = $i65k;
			$header[10] = $i256;
			$header[11] = $i1;
			$header[12] = $N16M;
			$header[13] = $N65k;
			$header[14] = $N256;
			$header[15] = $N1;

			//echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
			echo "partializing i:".$i.", 16M:".$i16M." 65k:".$i65k." 256:".$i256." 1:".$i1."/N:".$N.", 16M:".$N16M." 65k:".$N65k." 256:".$N256." 1:".$N1."\r\n";
			print_r($header);
			echo "\r\n";
			$subResponseDatagramArr = array_merge($header, $subMsgByteArr);
			$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $OpenedReqDemanding->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $OpenedReqDemanding->EncryptionIVDefault);

			//responsePayload<$ith, $subResponseDatagramEncrypted>
			$OpenedReqDemanding->responseDatagrams[$i] = $subResponseDatagramEncrypted;
			socket_sendto($OpenedReqDemanding->socket, $subResponseDatagramEncrypted, 416, 0, $OpenedReqDemanding->IP, $OpenedReqDemanding->port);
			$i = $i + 1;
		}
		//resp |400|...|400|0 < THIS < 400|
		if(strlen($json)!=0) {
			echo $i . "/" . $N . " " . strlen($json) . " " . $json . "\r\n";
			//FCK
			//adjust header
			//$iUpper = floor($i / 255);
			//$iLower = ($i % 255);
			//$NUpper = floor($N / 255);
			//$NLower = ($N % 255);

			//Decompose number i
			$i16M = 0;
			$i65k = 0;
			$i256 = 0;
			$i1 = 0;
			$this->EncodeIntegerTo4B($i,$i16M,$i65k,$i256,$i1);

			//Decompose number N
			$N16M = 0;
			$N65k = 0;
			$N256 = 0;
			$N1 = 0;
			$this->EncodeIntegerTo4B($N,$N16M,$N65k,$N256,$N1);
			//--
			//$header[8] = $iUpper;
			//$header[9] = $iLower;
			//$header[10] = $NUpper;
			//$header[11] = $NLower;
			$header[8] = $i16M;
			$header[9] = $i65k;
			$header[10] = $i256;
			$header[11] = $i1;
			$header[12] = $N16M;
			$header[13] = $N65k;
			$header[14] = $N256;
			$header[15] = $N1;

			//echo $i . " partializing" . $iUpper . "(255)" . $iLower . "(1)" . "/" . $NUpper . "(255)" . $NLower . "(1)\r\n";
			echo "partializing i:".$i.", 16M:".$i16M." 65k:".$i65k." 256:".$i256." 1:".$i1."/N:".$N.", 16M:".$N16M." 65k:".$N65k." 256:".$N256." 1:".$N1."\r\n";
			print_r($header);
			echo "\r\n";
			$msgString = $json;
			$msgByteArr = unpack('C*', $msgString);

			$subResponseDatagramArr = array_merge($header, $msgByteArr);
			$subResponseDatagramEncrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $OpenedReqDemanding->EncryptionKeyDefault, implode(array_map("chr", $subResponseDatagramArr)), MCRYPT_MODE_CBC, $OpenedReqDemanding->EncryptionIVDefault);

			//responsePayload<$ith, $subResponseDatagramEncrypted>
			$OpenedReqDemanding->responseDatagrams[$i] = $subResponseDatagramEncrypted;
			socket_sendto($OpenedReqDemanding->socket, $subResponseDatagramEncrypted, 416, 0, $OpenedReqDemanding->IP, $OpenedReqDemanding->port);
		}
	}

	//Encode Int32 to 4 bytes for transfer
	public function EncodeIntegerTo4B($number, &$n16M, &$n65k, &$n256, &$n1)
	{
		$auxiliary = $number;
		$n16M = floor($auxiliary/16777216); // 4th B, 2^24 ~ 16777216
		$auxiliary = ($auxiliary%16777216);
		$n65k = floor($auxiliary/65536); // 3rd B, 2^16 ~ 65536
		$auxiliary = ($auxiliary%65536);
		$n256 = floor($auxiliary/256); // 2nd B, 2^8 ~ 256
		$auxiliary = ($auxiliary%256);
		$n1 = floor($auxiliary/1); // 1st B, 2^0 ~ 1
	}

	//Decode 4 bytes from datagram to Int32
	public function Decode4BToInteger($n16M, $n65k, $n256, $n1)
	{
		$_result = ($n16M*16777216)+($n65k*65536)+($n256*256)+($n1*1);
		return $_result;
	}

	//defaut ctor
	public function __construct()
	{
		//empty
	}
	//ctor w/ arguments
	public static function Constructor($socket, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $IP, $port, $timestamp, $EncryptionKeyDefault, $EncryptionIVDefault,  $content, $quantity)
	{
		$instance = new self();
		//rezie
		$instance->socket = $socket;
		$instance->tokens = $tokens;
		//mysqli managers
		$instance->postsManager = $postsManager;
		$instance->usersManager = $usersManager;
		$instance->cupsManager = $cupsManager;
		$instance->positionsManager = $positionsManager;
		$instance->clubsManager = $clubsManager;
		//conn
		$instance->IP = $IP;
		$instance->port = $port;
		$instance->timestamp = $timestamp;
		//encryption
		$instance->EncryptionKeyDefault = $EncryptionKeyDefault;
		$instance->EncryptionIVDefault = $EncryptionIVDefault;
		//misc
		$instance->content = $content;
		$instance->quantity = $quantity;
		$instance->optID = null;
		$instance->canBeDeleted = false;

		$instance->responsePayload = array();
		echo $IP . ":" . $port . " REQ queued TBP \r\n";
		return $instance;
	}
	//ctor w/ arguments &optID
	public static function ConstructorWithId($socket, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $IP, $port, $timestamp, $EncryptionKeyDefault, $EncryptionIVDefault, $content, $quantity, $id)
	{
		$instance = new self();
		//rezie
		$instance->socket = $socket;
		$instance->tokens = $tokens;
		//mysqli managers
		$instance->postsManager = $postsManager;
		$instance->usersManager = $usersManager;
		$instance->cupsManager = $cupsManager;
		$instance->positionsManager = $positionsManager;
		$instance->clubsManager = $clubsManager;
		//conn
		$instance->IP = $IP;
		$instance->port = $port;
		$instance->timestamp = $timestamp;
		//encryption
		$instance->EncryptionKeyDefault = $EncryptionKeyDefault;
		$instance->EncryptionIVDefault = $EncryptionIVDefault;
		//misc
		$instance->content = $content;
		$instance->quantity = $quantity;
		$instance->optID = $id;
		$instance->canBeDeleted = false;

		$instance->responsePayload = array();
		echo $IP . ":" . $port . " REQ " . $id . " queued TBP \r\n";
		return $instance;
	}
}

class OpenedData extends Transaction
{
	// rezie
	public $socket;
	public $tokens;
	//managers
	public $postsManager = null;
	public $usersManager = null;
	public $cupsManager = null;
	public $positionsManager = null;
	public $clubsManager = null;
	//sender identification info
	public $IP;
	public $port;
	public $timestamp; //milisekundy
	//encryption
	public $EncryptionKeyDefault;
	public $EncryptionIVDefault;
	// Header info
	public $content;
	public $quantity;
	public $handle;
	public $optID;
	//count of incoming datagrams
	public $payloadCNT;
	//list of id => payload
	public $payload;
	//aux var operation finalised
	public $canBeDeleted;

	public function Type()
	{
		return Action::DATA;
	}

	//forking based on Transaction details
	public function InsertData()
	{
		//decission tree
		switch ($this->content) {
			case Content::AKT:
				switch ($this->quantity) {
					case Quantity::SINGLE:
						switch ($this->handle) {
							case Handle::CREATE:
								echo "SYS dumping payload\r\n";
								//echo print_r($this->payload);
								asort($this->payload);
								echo "SYS dumping again";
								//echo print_r($this->payload);

								$msg = "";
								foreach ($this->payload as $part) {
									//echo "SYS content".$part->content;
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);
								//$p = new Post($data[id], $data[timestamp], $data[title], $data[content]);
								//echo "MSG: ".print_r($msg)."\r\n";
								//echo "DATA ".$data."\r\n";
								echo "DATA: " . print_r($data) . "\r\n";
								if ($this->postsManager->addNewPost($data['title'], $data['content'])) {
									echo "true \r\n";
									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
							case Handle::UPDATE:
								//update skoro same but whole Post p
								echo "SYS dumping payload\r\n";
								//echo print_r($this->payload);
								asort($this->payload);
								echo "SYS dumping again";
								//echo print_r($this->payload);

								$msg = "";
								foreach ($this->payload as $part) {
									//echo "SYS content".$part->content;
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);
								//$p = new Post($data[id], $data[timestamp], $data[title], $data[content]);
								//echo "MSG: ".print_r($msg)."\r\n";
								//echo "DATA ".$data."\r\n";
								echo "DATA: " . print_r($data) . "\r\n";
								if ($this->postsManager->updatePost($this->optID, $data['title'], $data['content'])) {
									echo "true \r\n";
									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
						}
						break;
				}
				break;
			case Content::ZAV:
				switch ($this->quantity) {
					case Quantity::SINGLE:
						switch($this->handle){
							case Handle::CREATE:
								//create zavod podle toho co mi prislo
								echo "SYS dumping payload\r\n";
								asort($this->payload);
								$msg = "";
								foreach ($this->payload as $part) {
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);
								echo "DATA: " . print_r($data) . "\r\n";
								if($this->cupsManager->insertNewCupFromAdmin($data['name'], $data['date'], $data['club'], $data['content'])){
									echo "true \r\n";
									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
							case Handle::UPDATE:
								//mozna tbi later, upravit existujici zavod z mobilu..hmm
								break;
						}
						break;
				}
				break;
			case Content::USR:
				switch ($this->quantity) {
					case Quantity::SINGLE:
						echo "SYS dumping payload\r\n";
						//echo print_r($this->payload);
						asort($this->payload);
						echo "SYS dumping again";
						//echo print_r($this->payload);

						$msg = "";
						foreach ($this->payload as $part) {
							//echo "SYS content".$part->content;
							$msg = $msg . $part->content;
						}
						$data = json_decode($msg, true);
						echo "DATA: " . print_r($data) . "\r\n";
						if ($this->usersManager->registerUserFromAdminWrap($data['first_name'], $data['last_name'], $data['email'], $data['password'], $data['prava'], $data['klub'])) {
							echo "true \r\n";
							$this->canBeDeleted = true;
							return true;
						} else {
							echo "false";
							$this->canBeDeleted = true;
							return false;
						}
						break;
				}
				break;
			case Content::PAIRINGPZ:
				switch ($this->quantity) {
					case Quantity::LISTING:
						switch ($this->handle) {
							case Handle::CREATE:
								echo "never happens :) \r\n";
								break;
							case Handle::UPDATE:
								echo "SYS dumping payload \r\n";
								//echo print_r($this->payload);
								asort($this->payload);
								echo "SYS dumping again \r\n";
								//echo print_r($this->payload);

								$msg = "";
								foreach ($this->payload as $part) {
									//echo "SYS content".$part->content;
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);
								echo "PAIRING PZ: " . print_r($data) . " for " . $this->optID . "\r\n";
								//if insert succ
								if ($this->cupsManager->updatePairingForThisCup($this->optID, $data)) {
									echo "true";

									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
						}
						break;
				}
				break;
			case Content::PAIRINGRZ:
				switch ($this->quantity) {
					case Quantity::LISTING:
						switch ($this->handle) {
							case Handle::CREATE:
								echo "never happens :) \r\n";
								break;
							case Handle::UPDATE:
								echo "SYS dumping payload \r\n";
								//echo print_r($this->payload);
								asort($this->payload);
								echo "SYS dumping again \r\n";
								//echo print_r($this->payload);

								$msg = "";
								foreach ($this->payload as $part) {
									//echo "SYS content".$part->content;
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);
								echo "PAIRING RZ: " . print_r($data) . " for " . $this->optID . "\r\n";
								//if insert succ
								if ($this->cupsManager->updateAvailabilityForThisCup($this->optID, $data)) {
									echo "true";
									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
								break;
						}
						break;
					//case Content::PAIRINGP
					//	switch ($this->quantity) {
					//		case Quantity::LISTING:
					//			//handle
					//			break;
					//	}
					//  break;
				}
			case Content::MEFORTHECUP:
				switch ($this->quantity){
					case Quantity::SINGLE:
						switch ($this->handle){
							case Handle::CREATE:
								echo "SYS dumping payload\r\n";
								asort($this->payload);
								echo "SYS dumping again";

								$msg = "";
								foreach ($this->payload as $part) {
									$msg = $msg . $part->content;
								}
								$data = json_decode($msg, true);

								echo "DATA: " . print_r($data) . "\r\n";
								if ($this->cupsManager->addAvailableUserForTheCup($data['idcup'], $data['iduser'])) {
									echo "true \r\n";
									$this->canBeDeleted = true;
									return true;
								} else {
									echo "false";
									$this->canBeDeleted = true;
									return false;
								}
								break;
						}
						break;
				}
				break;
		}
	}

	//DEPRECATE...resim count==0
	public function PayloadComplete()
	{
		//spocitat nejak debilinu asi $key=>bool
		if (true) {
			return true;
		} else if (false) {
			return false;
		}
	}

	//Constructors
	public function __construct()
	{
		//just constructor
	}

	//dofitovat konstruktory dalsimi detaily
	public static function Constructor($socket, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $IP, $port, $timestamp, $EncryptionKeyDefault, $EncryptionIVDefault, $content, $quantity, $handle, $payloadCNT)
	{
		$instance = new self();
		//rezie
		$instance->socket = $socket;
		$instance->tokens = $tokens;
		//mysqli managers
		$instance->postsManager = $postsManager;
		$instance->usersManager = $usersManager;
		$instance->cupsManager = $cupsManager;
		$instance->positionsManager = $positionsManager;
		$instance->clubsManager = $clubsManager;
		//conn
		$instance->IP = $IP;
		$instance->port = $port;
		$instance->timestamp = $timestamp;
		//encryption
		$instance->EncryptionKeyDefault = $EncryptionKeyDefault;
		$instance->EncryptionIVDefault = $EncryptionIVDefault;
		//misc
		$instance->content = $content;
		$instance->quantity = $quantity;
		$instance->handle = $handle;
		$instance->payloadCNT = $payloadCNT;

		$instance->optID = null; //no ID constructor
		$instance->payload = array();
		$instance->canBeDeleted = false;

		return $instance;
	}

	public static function ConstructorWithId($socket, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $IP, $port, $timestamp, $EncryptionKeyDefault, $EncryptionIVDefault, $content, $quantity, $handle, $payloadCNT, $optID)
	{
		$instance = new self();
		//rezie
		$instance->socket = $socket;
		$instance->tokens = $tokens;
		//mysqli manager
		$instance->postsManager = $postsManager;
		$instance->usersManager = $usersManager;
		$instance->cupsManager = $cupsManager;
		$instance->positionsManager = $positionsManager;
		$instance->clubsManager = $clubsManager;
		//conn
		$instance->IP = $IP;
		$instance->port = $port;
		$instance->timestamp = $timestamp;
		//encryption
		$instance->EncryptionKeyDefault = $EncryptionKeyDefault;
		$instance->EncryptionIVDefault = $EncryptionIVDefault;
		//misc
		$instance->content = $content;
		$instance->quantity = $quantity;
		$instance->handle = $handle;
		$instance->payloadCNT = $payloadCNT;
		$instance->optID = $optID; //id constructor, id contained

		$instance->payload = array();
		$instance->canBeDeleted = false;

		return $instance;
	}
}

class ApproveUser extends Transaction
{
	//socket
	public $socket;
	//managers
	public $usersManager = null;
	//sender identification info
	public $IP;
	public $port;
	public $timestamp;
	//encryption
	public $EncryptionKeyDefault;
	public $EncryptionIVDefault;
	//related content
	public $userID;
	public $canBeDeleted;

	public function Type()
	{
		return Action::APV;
	}
	public function SatisfyReq()
	{
		if($this->usersManager->approveUser($this->userID)) {
			//send FIN //maybe tripple
			//deletable true
			//implemented in the loop now
			return true;
		}
		else {
			//send ERR /maybe triple
			//deletable true
			//implemented in the loop now
			return false;
		}
	}
	public function __construct()
	{

	}
	//Not available, can be constructed only with userID
	private static function Constructor()
	{

	}
	//Only available constructor
	public static function ConstructorWithId($socket, $usersManager, $IP, $port, $timestamp, $EncryptionKeyDefault, $EncryptionIVDefault, $userID)
	{
		$instance = new self();
		//socket
		$instance->socket = $socket;
		//mysqli managers
		$instance->usersManager = $usersManager;
		//connection info
		$instance->IP = $IP;
		$instance->port = $port;
		$instance->timestamp = $timestamp;
		//encryption
		$instance->EncryptionKeyDefault = $EncryptionKeyDefault;
		$instance->EncryptionIVDefault = $EncryptionIVDefault;
		//misc
		$instance->userID = $userID;
		$instance->canBeDeleted = false;
		echo $IP . ":" . $port . " APV " . $userID . " queued TBA \r\n";
		return $instance;
	}
}

class Payload
{
	public $Nth;
	public $outOfN;
	public $content;

	//konstruktor
	public function __construct($Nth, $outOfN, $content)
	{
		$this->Nth = $Nth;
		$this->outOfN = $outOfN;
		$this->content = $content;
	}
}

class Token
{
	//Do I even need these?
	//public $IP; //192.168.1.1
	//public $port; //8888
	public $status; //wtf status? nebudu jen pocitat ts-ts<30m? exp then
	//Authorization
	public $id; //7
	public $first_name; //Lukas
	public $last_name; //Kousal
	public $affiliation; // 2
	public $affil_text; // PoPro
	public $rights; // 0
	public $auth_tkn; // a5R9T5g7t8
	public $timestamp; // 1551627226049

	private function generateTkn($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$_randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$_randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $_randomString;
	}

	private function getTimestamp()
	{
		$_timestamp = round(microtime(true) * 1000);
		return $_timestamp;
	}

	public function __construct($_status, $_id, $_first_name, $_last_name, $_affiliation, $_affil_text, $_rights)
	{
		$this->status = $_status;
		$this->id = $_id;
		$this->first_name = $_first_name;
		$this->last_name = $_last_name;
		$this->affiliation = $_affiliation;
		$this->affil_text = $_affil_text;
		$this->rights = $_rights;
		$this->auth_tkn = $this->generateTkn();
		$this->timestamp = $this->getTimestamp();
	}
	//full Serialization
	public function Serialize()
	{
		$_token_serialized="{\"id\":\"".$this->id."\",\"first_name\":\"".$this->first_name."\",\"last_name\":\"".$this->last_name."\",\"affiliation\":\"".$this->affiliation."\",\"affil_text\":\"".$this->affil_text."\",\"rights\":\"".$this->rights."\",\"auth_tkn\":\"".$this->auth_tkn."\",\"timestamp\":\"".$this->timestamp."\"}";
		return $_token_serialized;
	}
	public function getStatus()
	{
		return $this->status;
	}
	public function getAuthTkn()
	{
		return $this->auth_tkn;
	}
}

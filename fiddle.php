<?php
//TODO list
//auth mechanism before Transactions creation, encrypting plaintext datagram(?)
//copy read new erase from old buffer, "stop mechanism" to avoid infinite loop1 FUUUUUUU
//wrap Transaction to Authed object smhw
//copy incoming, process, then delete these datagrams from normal queue and continue next iteration
//sym encryption of all UDP packets app-phone communication... i.e. md5(year-day+month)
//md5(salt1|date thing|salt2)
//logging https://www.php.net/manual/en/function.file-put-contents.php
//https://stackoverflow.com/questions/1768894/how-to-write-into-a-file-in-php
require '../start.php';
if (!defined('MSG_DONTWAIT')) define('MSG_DONTWAIT', 0x40);
//http://php.net/manual/en/function.socket-recv.php
require 'UDPHandler.php';


//Start time of script - for logging frequency
$startTime = time();
//logging destination
$loggingOutput = EvLoggingType::TERMINAL;
$loggingFrequency = EvLoggingFrequency::ONCEPERHOUR;
//terminal debug TRUE/FALSE,
$termDebug = TerminalDebug::FALSE;


//Socket
$s = socket_create(AF_INET, SOCK_DGRAM, 0);
$protocol_handler = new UDPHandler($s, $postsManager, $usersManager,
	$cupsManager, $positionsManager,
	$clubsManager);
//Logging
//CREATE FILE HERE

//socket_set_option($s, SOL_SOCKET, SO_REUSEADDR, 1);

//Binding for outside world, rewrite to echo here on true or false
$bind_on_url = "localhost";
$bind_on_port = "8888";
//$protocol_handler->BindOn($bind_on_url, $bind_on_port);
if($protocol_handler->BindOn($bind_on_url, $bind_on_port)==true)
{
	echo 'Server successful bound on ' . $bind_on_url . ':' . $bind_on_port . "\r\n";
}
else
{
	echo 'Bind error';
}

$read = array($s);
//$except = array();

$write = NULL;
$except = NULL;

//$num_changed_sockets = socket_select($read, $write, $except, 0);

//IP:PORT=>token
//REWRITE ath_tkn=>token
$tokens = array();

// IP => $Incoming child
$openedTransactions = array();

echo "SERVER READY\r\n";
while (true) {
$clients = $read;
// all $IP => String received in this iteration
$receivedDatagrams = array();
//1.0s tj. 1000ms fce: 1000000
//0.5s tj.  500ms fce:  500000
//0.2s tj.  200ms fce:  200000
//0.1s tj.  100ms fce:  100000
//.05s tj.   50ms fce:   50000
usleep(500000);
//sleep(1);
//usleep(Constants::LOOP_TICK);
echo "TICK\r\n";

//FIRST STAGE - READ INCOMING DATAGRAMS AND FILL QUEUE
$num_changed_sockets = socket_select($clients, $write, $except, 0);
if ($num_changed_sockets === false) {
	echo "socket_select() failed, reason: " .
		socket_strerror(socket_last_error()) . "\r\n";
}
elseif ($num_changed_sockets === 0) {
	//sleep(1);
	usleep(50000);
}
else if ($num_changed_sockets > 0) {
	//echo $num_changed_sockets . "pending sockets\r\n";
	while (socket_select($clients, $write, $except, 0) > 0) {
		foreach ($read as $read_sock) {
			$timestamp = round(microtime(true) * 1000);
			//w/o padding                              416
			socket_recvfrom($read_sock, $buf, 416, 0, $rip, $rport);
			$address = $rip . ":" . $rport . ":" . $timestamp;
			//N/E version

			//Decrypt incoming, trim the zeroes (32bit)
			$decrypted_datagram = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $protocol_handler->EncryptionKeyDefault, $buf, MCRYPT_MODE_CBC, $protocol_handler->EncryptionIVDefault);
			//$decrypted_buf = rtrim($decrypted_buf, "\0");

			$receivedDatagrams[$address] = $decrypted_datagram;
			//echo "buffer ma " . strlen($buf) . " characteru\r\n";
			echo $rip . ":" . $rport . " - read in " . $timestamp ."\r\n";
			//0.01s tj. 10ms
			usleep(10000);
		}
	}
}

//IDENTIFY and PROCESS ARRIVED&DECRYPTED DATAGRAMS in the buffer
foreach ($receivedDatagrams as $senderInfo => $datagram) {
		//key processing
		$senderSplit = explode(":", $senderInfo);
		$ip = $senderSplit[0];
		$port = $senderSplit[1];
		$stamp = $senderSplit[2];

		//key
		$transKeyIpPort = $ip . ":" . $port;

		//AUX vypis
		echo $ip . ":" . $port . " arrived " . $stamp . " => B\"" . $datagram . "\"E";
		echo "\r\n";

		//PART 1 Processing of buffered datagrams
		$action = $protocol_handler->DesiredAction($datagram);
		echo "SYS action is:" . $action . ", proceeding... \r\n";
		switch ($action) {
			//Create and queue OpenedReq object w/ or w/o id
			case Action::REQ:
				//authorize request from phone client
				$alleged_auth_tkn = $protocol_handler->UserAuthorizationToken($datagram);
				//if (array_key_exists($transKeyIpPort, $tokens)) {
				if (array_key_exists($alleged_auth_tkn, $tokens)) {
					//if ($tokens[$transKeyIpPort]->auth_tkn == $alleged_auth_tkn) {
						$content = $protocol_handler->DesiredContent($datagram);
						$quantity = $protocol_handler->DesiredQuantity($datagram);
						echo "content:" . $datagram[1] . "quantity:" . $datagram[2] . "\r\n";
						echo "content:" . $content . "quantity:" . $quantity . "\r\n";
						$id = $protocol_handler->DesiredID($datagram);
						//echo "vypis condition id: ".$id;
						//$request = null;
						if ($id == null) {
							$request = OpenedReq::Constructor($s, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $ip, $port, $stamp, $protocol_handler->EncryptionKeyDefault, $protocol_handler->EncryptionIVDefault, $content, $quantity);
							$openedTransactions[$transKeyIpPort] = $request;
						} else {
							$request = OpenedReq::ConstructorWithId($s, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $ip, $port, $stamp, $protocol_handler->EncryptionKeyDefault, $protocol_handler->EncryptionIVDefault, $content, $quantity, $id);
							$openedTransactions[$transKeyIpPort] = $request;
						}
					/*} else {
						echo "TOKEN MISMATCH, REQ REJECTED FOR " . $transKeyIpPort . "; arrived:".$alleged_auth_tkn.", session w/: ".$tokens[$transKeyIpPort]->auth_tkn.".\r\n";
					}*/
				} else {
					echo "NO VALID REQ REJECTED FOR " . $transKeyIpPort . ", DUMPING ACTIVE TOKEN CONNECTIONS\r\n";
					print_r($tokens);
					//TO THINK ABOUT MAYBE THE SHIFT PLAYS A ROLE HERE? +2 offset of token????
				}
				break;
			//Create and queue OpenedData object; wait for new data to come
			case Action::FDATA:
				//authorize request from phone client
				$alleged_auth_tkn = $protocol_handler->UserAuthorizationToken($datagram);
				//if (array_key_exists($transKeyIpPort, $tokens)) {
				if (array_key_exists($alleged_auth_tkn, $tokens)) {
					//if ($tokens[$transKeyIpPort]->auth_tkn == $alleged_auth_tkn) {
						$content = $protocol_handler->DesiredContent($datagram);
						$quantity = $protocol_handler->DesiredQuantity($datagram);
						$handle = $protocol_handler->DesiredHandle($datagram);
						$optID = $protocol_handler->DesiredID($datagram);
						$payloadCNT = $protocol_handler->DesiredPayloadTotal($datagram);
						if ($optID == null) {
							echo "OpenedData registered\r\n";
							$fdata = OpenedData::Constructor($s, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $ip, $port, $stamp, $protocol_handler->EncryptionKeyDefault, $protocol_handler->EncryptionIVDefault, $content, $quantity, $handle, $payloadCNT);
							$openedTransactions[$transKeyIpPort] = $fdata;
							$protocol_handler->Acknowledge($s, $ip, $port);
							echo "ACK sent\r\n";
						} else {
							echo "OpenedData registered\r\n";
							$fdata = OpenedData::ConstructorWithId($s, $tokens, $postsManager, $usersManager, $cupsManager, $positionsManager, $clubsManager, $ip, $port, $stamp, $protocol_handler->EncryptionKeyDefault, $protocol_handler->EncryptionIVDefault, $content, $quantity, $handle, $payloadCNT, $optID);
							$openedTransactions[$transKeyIpPort] = $fdata;
							$protocol_handler->Acknowledge($s, $ip, $port);
							echo "ACK sent\r\n";
						}
					/*} else {
						echo "TOKEN MISMATCH, FDATA REJECTED FOR " . $transKeyIpPort . "; arrived:".$alleged_auth_tkn.", session w/: ".$tokens[$transKeyIpPort]->auth_tkn.".\r\n";
					}*/
				} else {
					echo "NO VALID FDATA REJECTED FOR " . $transKeyIpPort . "\r\n";
				}
				break;
			//Handle particular part of payload
			case Action::DATA:
				//tf
				echo "SYS Action::DATA Action::DATA r/n \r\n";
				$Nth = $protocol_handler->DesiredPayloadNumber($datagram);
				$outOfN = $protocol_handler->DesiredPayloadTotal($datagram);
				$content = $protocol_handler->DesiredPayloadContent($datagram);
				//test if object exists
				if (array_key_exists($transKeyIpPort, $openedTransactions)) {
					//pick the object
					$var = $openedTransactions[$transKeyIpPort];
					//payload construct the object?!
					$payloadPart = new Payload($Nth, $outOfN, $content);
					//object payload list add
					$var->payload[$Nth] = $payloadPart;
					echo "SYS Action::DATA dumping current payload ordering \r\n";
					//print_r($var->payload);
					echo "SYS Action::DATA done \r\n";
				} else {
					echo "SYS DROPPING THIS DATAGRAM, NO AFFILIATION WHATSOEVER \r\n";
				}
				//if this is OK then fix the function triggering the evaluation

				break;
			//Handle APV
			case Action::APV:
				//authorize apv from phone client

				//TODO user_ID code higher as Int32, $protocol_handler->UserAuthorizationToken($datagram, 4) to 4!!
				//ATTENTION! authorization token offset: 2, normally REQ and FDATA has implicit arg 0
				//this datagram looks like, user_ID is user to be authorized
				//|0 APV|...|16+17+18+19 user_ID|18-28 token|...till 415| therefore authorization token has offset 2 so far
				$alleged_auth_tkn = $protocol_handler->UserAuthorizationToken($datagram, 4);
				//if (array_key_exists($transKeyIpPort, $tokens)) {
				if(array_key_exists($alleged_auth_tkn, $tokens)) {
					//if ($tokens[$transKeyIpPort]->auth_tkn == $alleged_auth_tkn) {
						$userID = $protocol_handler->DesiredApproveUser($datagram);
						$approve = ApproveUser::ConstructorWithId($s, $usersManager, $ip, $port, $stamp, $protocol_handler->EncryptionKeyDefault, $protocol_handler->EncryptionIVDefault, $userID);
						$openedTransactions[$transKeyIpPort] = $approve;

					/*} else {
						echo "TOKEN MISMATCH, APV REJECTED FOR " . $transKeyIpPort . "; arrived:" . $alleged_auth_tkn . ", session w/: " . $tokens[$transKeyIpPort]->auth_tkn . " authorizing ".$protocol_handler->DesiredApproveUser($datagram).".\r\n";
					}*/
				} else {
					echo "NO VALID APV REJECTED FOR " . $transKeyIpPort . "\r\n";
				}
				break;
			//DEPRECATE only in direct communication
			case Action::ACK:
				//NOT NEEDED HERE, ACKs ARE IN DIRECT TRANSACTIONS
				break;
			//Handle RESEND
			case Action::RESEND:
				//$var = OpenedReq
				$var = $openedTransactions[$transKeyIpPort];
				//|0=RES|...|16=$ith_upper|17=$ith_lower|...|415=NA|
				$ith = $protocol_handler->DesiredResend($datagram);
				//OpenedReq->responseDatagrams[$ith] <$ith, $subResponseDatagramEncrypted
				$subResponseDatagramEncrypted = $var->responseDatagrams[$ith];
				socket_sendto($s, $subResponseDatagramEncrypted, 416, 0, $ip, $port);
				//$content = (((OpenedData)($var))->payload[id])->content;
				//socket_sendto($s, ) jeste header
				break;
			//Handle FIN
			case Action::FIN:
				//|101|...|N/A|
				//$var = OpenedReq
				$var = $openedTransactions[$transKeyIpPort];
				$var->canBeDeleted = true;
				//IP:PORT => finalised OK; WILL BE DELETED FROM OPENED TRANSACTIONS
				break;
			//Handle LOGIN
			case Action::LOGIN:
				$credentials = $protocol_handler->DecodeCredentials($protocol_handler->DesiredPayloadContent($datagram));
				echo "User is trying to log in, user: ".$credentials["user"]." with pass: ".$credentials["pass"].".\r\n";
				$authPack = $protocol_handler->Authenticate($credentials["user"],$credentials["pass"]);
				//state of logging in
				if($authPack->getStatus()==Action::UNFOUND)
				{
					echo "user UNFOUND\r\n";
					$protocol_handler->TripleUNFOUND($s, $ip, $port);
				}
				else if($authPack->getStatus()==Action::WRONGCRED)
				{
					echo "user WRONGCRED\r\n";
					$protocol_handler->TripleWRONGCRED($s, $ip, $rport);
				}
				else
				{
					echo $authPack->Serialize() . "\r\n";
					//echo strlen($authPack->Serialize()) . "\r\n";
					echo "user SUCC, sending token\r\n";

					//OLD key IP:PORT
					//if(array_key_exists($transKeyIpPort, $tokens))
					if(array_key_exists($authPack->auth_tkn, $tokens))
					{
					//	unset($tokens[$transKeyIpPort]);
						unset($tokens[$authPack->auth_tkn]);
					}
					//OLD [IP:PORT]
					//$tokens[$transKeyIpPort] = $authPack;
					$tokens[$authPack->auth_tkn] = $authPack;
						//Delete this dump and make routinely occuring dump of logged users
						echo "active ppl in system\r\n";
						var_dump($tokens);
						//end of delete
					$protocol_handler->TripleSUCC($s, $ip, $port, $authPack->Serialize());
				}
				//$tokens[$IP:port]=$authPack;
				//sendBack($authPack)
				break;
			//Handle Invalidation of mobile token for Xamarin
			case Action::INVTKN:
				$auth_tkn = $protocol_handler->UserAuthorizationToken($datagram, 0);
				//if user authenticated

				if(array_key_exists($auth_tkn, $tokens))
				{
					unset($tokens[$auth_tkn]);
				}
				else
				{
					//if user is not authenticated, whatever
				}
				break;
			default:
				//Invalid action, huh asi ignore
				echo "Invalid action";
				break;
			//implement payload buffers everywhere and destroy object after FIN
		}
}

//PART 2 Processing of opened Transactions
$now = round(microtime(true) * 1000);
//Loop All Object Handlings, timestamp<3min, have all payload things
foreach ($openedTransactions as $key => $transaction) {
//can be finalised?
	if ($transaction->Type() == Action::REQ) {
		//Grab REQ & Time Difference
		$REQ = $transaction;
		$timeDifference = ($now - $REQ->timestamp);

		//Expired
		if (($timeDifference) > Constants::TRANSACTION_EXPTIME) {
			echo $key . " EXP-ING REQ\r\n";
			unset($openedTransactions[$key]);
			continue;
		}

		//Deletable
		if ($REQ->canBeDeleted == true) {
			echo $key . " FIN-ING REQ\r\n";
			unset($openedTransactions[$key]);
			continue;
		}

		//Satisfy
		echo $key . " Satisfying REQ\r\n";
		$REQ->SatisfyReq();
	}
	else if ($transaction->Type() == Action::DATA) {
		//Grab DATA & Time Difference
		$DATA = $transaction;
		$timeDifference = ($now - $DATA->timestamp);

		//Expired
		if (($timeDifference) > Constants::TRANSACTION_EXPTIME) {
			unset($openedTransactions[$key]);
			continue;
		}

		//Deletable
		if (($DATA->canBeDeleted) == true) {
			unset($openedTransactions[$key]);
			continue;
		}

		// PICI CO TO TU ROBI ?!!!!
		//try delete (?)
		//$write = array();

		//Received And Ready
		if (($timeDifference) > Constants::PAYLOAD_RCVDTIME) {
			$missingPayload = $protocol_handler->MissingPayloadParts($DATA);
			echo "SYS missing Payload " . print_r($missingPayload) . "\r\n";
			//count number of missing parts of payload
			//if none, execute insert
			if (0 == count($missingPayload)) {
				echo "SYS zero.\"\r\n\"";
				if ($DATA->InsertData()) {
					//$protocol_handler->Finish($s, $DATA->IP, $DATA->port);
					//$protocol_handler->TripleACK($s, $DATA->IP, $DATA->port);
					echo "SYS insert SUCC \r\n";
					//$protocol_handler->Acknowledge($s, $DATA->IP, $DATA->port);
					$protocol_handler->TripleACK($s, $DATA->IP, $DATA->port);
				} else {
					$protocol_handler->Error($s, $DATA->IP, $DATA->port);
					echo "SYS insert FAIL \r\n";
				}
				//T0D0 if succ, send FIN
			} //else request missing parts
			else {
				echo "SYS rereqing \"\r\n\"";
				foreach ($missingPayload as $id) {
					echo "SYS rerequing: ".$id."\r\n";
					$protocol_handler->RequestMissingPart($id, $DATA->IP, $DATA->port);
				}
			}
		}
		//if payload complete -> insert
		//if ne, rerequest missing parts
	}
	else if ($transaction->Type() == Action::APV) {
		//Grab APV & Time Difference
		$APV = $transaction;
		$timeDifference = ($now - $APV->timestamp);

		//Expired
		if (($timeDifference) > Constants::TRANSACTION_EXPTIME) {
			echo $key . " EXP-ING APV\r\n";
			unset($openedTransactions[$key]);
			continue;
		}

		//Deletable
		if ($APV->canBeDeleted == true) {
			echo $key . " FIN-ING APV\r\n";
			unset($openedTransactions[$key]);
			continue;
		}

		//Satisfy
		echo $key . " Satisfying APV\r\n";
		if($APV->SatisfyReq())
		{
			$protocol_handler->TripleFIN($s, $APV->IP, $APV->port);
			$APV->canBeDeleted=true;
		}
		else
		{
			$protocol_handler->TripleERR($s, $APV->IP, $APV->port);
			$APV->canBeDeleted=true;
		}
	}
}
	//sleep 0.1s
usleep(100000);
}

//TODO EXPIRE USERS 30m+

/*sleep(10);
echo "checking...\r\n";
$buf = '';
if(strlen($buf)==0)
{
echo "empty\r\n";
} else{
echo "not emtpy\r\n";
}*/
//$result = socket_recvfrom($s, $buf, 416, 0, $rip, $rport);
/*socket_recvfrom($s, $buf, 416, 0, $rip, $rport);
echo $buf;
echo "end, 5 sleep\r\n";
*/
//echo $result;
sleep(5);
?>

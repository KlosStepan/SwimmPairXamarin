using System;
using System.Collections.Generic;
using System.Net;
using System.Net.Sockets;
using System.Text;
using Newtonsoft.Json;
using Newtonsoft.Json.Converters;
using System.Linq;
using System.Security.Cryptography;
using System.Threading;

namespace XamarinLTest
{
    ////Enumerations Used For Datagram Encoding

    // datagram[0]
    enum Action { REQ=0, DATA=1, FDATA=11, APV=2,
                  ACK=100, FIN=101, RESEND=102, ERR=103,
                  LOGIN=200, SUCC=201, UNFOUND=202, WRONGCRED=203, NOTLOGED=204, EXPIRED=205, UNDEF=206, INVTKN=210 };
    // 
    // datagram[1]
    enum Content { AKT=0, ZAV=1, USR=2, NAUSR=3, CLUB=4,
                   PAIRINGRZ=20, PAIRINGPZ=21,
                   POSITIONS=30,
                   CLUBFRIENDS=40, CLUBFRIENDSFORCUP=41, MEFORTHECUP=42 };
    // datagram[2]
    enum Quantity { LISTING=0, SINGLE=1 };

    // datagram[3]
    enum Handle { CREATE=0, UPDATE=1,
                  ASC=100, DESC=101 };
    // datagram[4] datagram[5] datagram[6] datagram[7] optID
    // datagram[8] datagram[9] datagram[10] datagram[11] Nth
    // datagram[12] datagram[13] daragram[14] datagram[15] outOfN

    // Use As Null In Datagram /write only/
    enum NA { NA = 255 };

    public class UDPHandler
    {
        //UDPHandler Socket Handling Variables 
        Socket sock = new Socket(AddressFamily.InterNetwork, SocketType.Dgram, ProtocolType.Udp);
        IPAddress ServerAddr;
        EndPoint EndPoint;
        UdpClient s;

        //Construct upol logging in, Construct, LogIN

        //TODO Auth user or default settings
        // IP 
        // PORT

        //Authpack to be received upon authentication
        public AuthPack authpack = null;

        //Encryption stuff
        public String EncryptionKeyDefault;
        public String EncryptionIVDefault;

        //Times Constants
        public static TimeSpan HundrethOfSecond = TimeSpan.FromMilliseconds(10);
        public static TimeSpan FiftiethOfSecond = TimeSpan.FromMilliseconds(20);
        public static TimeSpan TwentiethOfSecond = TimeSpan.FromMilliseconds(50);
        public static TimeSpan TenthOfSecond = TimeSpan.FromMilliseconds(100);
        public static TimeSpan FifthOfSecond = TimeSpan.FromMilliseconds(200);
        public static TimeSpan HalfOfSecond = TimeSpan.FromMilliseconds(500);
        public static TimeSpan OneSecond = TimeSpan.FromMilliseconds(1000);
        public static TimeSpan TwoSeconds = TimeSpan.FromMilliseconds(2000);
        public static TimeSpan FiveSeconds = TimeSpan.FromMilliseconds(5000);

        //Positions Constants
        public String[] ResolveRights = new String[3] { "Rozhodčí", "Manažer klubu", "Administrátor SwimmPair" };

        //UDPHandler Constructor 
        public UDPHandler(String IP, Int32 Port, Int32 Timeout)
        {
            ServerAddr = IPAddress.Parse(IP);
            EndPoint = new IPEndPoint(ServerAddr, Port);
            sock.ReceiveTimeout = Timeout; //Whaaat?
            s = new UdpClient(IP, Port);
            
            //NOT AVAILABLE AT XAMARIN.ANDROID AND XAMARIN.IOS
            //Hardcoded key and IV, could be handshaked in the future
            //ECDiffieHellmanCng Class is neither supported by Xamarin nor it is available for .NET Standard 2.0 
            //https://docs.microsoft.com/en-us/dotnet/api/system.security.cryptography.ecdiffiehellmancng?view=netframework-4.7.2&viewFallbackFrom=netstandard-2.0

            //EncryptionKeyDefault = "M4GHwHt3xtraDlu0RvhOhIc8JZvV/wpJZiDOR4I/sBQ=";
            //EncryptionKeyDefault = "5SUUYId9Q5KWOlNnf1Tq9ARsbb92MgZ9";
            EncryptionKeyDefault = "YiyM/f2zNq5GkVmiUMJ8qVACaPaBBo5AJRqkIH9cgd4=";

            //EncryptionIVDefault = "vnzCI6yuUU8JBfUiJrtyORM2AUfASp5xbdND+b2NZwQ=";
            //EncryptionIVDefault = "YFPHmkIFZgH893UQiTp9pb8HTuP6IMkJ";
            EncryptionIVDefault = "tLafaj6+1YCiIIvbMO7iN/9QVLK1PPjRzARR9Fsh82c=";
        }

        //Asynchronous Receiver Handlers BULLSHIT DEPRECATE THIS ASAP
        public void UDPDatagramPostAsync(IAsyncResult result)
        {
            // this is what had been passed into BeginReceive as the second parameter:
            UdpClient socket = result.AsyncState as UdpClient;
            // points towards whoever had sent the message:
            IPEndPoint source = new IPEndPoint(0, 0);
            // get the actual message and fill out the source:
            byte[] message = socket.EndReceive(result, ref source);
            // do what you'd like with `message` here:
            Console.WriteLine("Got " + message.Length + " bytes from " + source);
            // schedule the next receive operation once reading is done:
            //socket.BeginReceive(new AsyncCallback(OnUdpData), socket);
            //FIN
            byte[] fin = makeFINDatagram();
            fin[0] = (byte)(Action.FIN);
            /*for (int i = 1; i <= 415; i++)
                fin[i] = (byte)255;
            */
            byte[] txtBuffer = new byte[400];
            Array.Copy(message, 16, txtBuffer, 0, (message.Length - 16));
            //Array.Copy()
            String txtString = System.Text.Encoding.UTF8.GetString(txtBuffer);
            txtString = txtString.Replace("\0", String.Empty)/*.Replace("\"", "\\").Replace("\"", String.Empty)*/;

            s.Send(fin, 416);
            Post p;
            if (Post.TryParse(txtString, out p))
            {
                //pRef = p;
            }
            else
            {
                //pRef = null;
            }
            //pRef = p;

            //asi vrat jen Post
        }
        public void UDPDatagramPostListAsync(IAsyncResult result)
        {
            //get JSON and deserialize
            //https://stackoverflow.com/questions/34103498/how-do-i-deserialize-a-json-array-using-newtonsoft-json
            //poskladej, rerequestuj, sloz vrat List<Post>
            UdpClient socket = result.AsyncState as UdpClient;
            IPEndPoint source = new IPEndPoint(0, 0);
            byte[] datagram = socket.EndReceive(result, ref source);

            int N = PayloadLength(datagram);
            List<String> delivery = new List<String>();
            if (N == 1)
            {
                byte[] message = new byte[400];
                Array.Copy(datagram, 16, message, 0, (datagram.Length - 16));
                String strContentOfMessage = System.Text.Encoding.UTF8.GetString(message);
                strContentOfMessage = strContentOfMessage.Replace("\0", String.Empty);
                delivery.Insert(1, strContentOfMessage);
            }
            else
            {
                for (int i = 1; i < N; i++)
                {
                    //byte[] v = s.BeginReceive(new AsyncCallback(UDPRecvNext), s);
                }
                //narecvuju zbytek
                //zkontroluju
                //dorequestuju+dorecvuju zbytek
                //slozim
            }
            //glue everything
            //parse everything
            //lpRef = delivery;
        }
        public byte[] UDPRecvNext(IAsyncResult result)
        {
            UdpClient socket = result.AsyncState as UdpClient;
            IPEndPoint source = new IPEndPoint(0, 0);
            byte[] datagram = socket.EndReceive(result, ref source);
            return datagram;
        }
        //TBD SHIT

        ////Datagram Identification

        //REWRITTEN 2B->4B: 8 9 10 11
        //read |0_DATA|...|8_posision_u|9_position_l|...|415|
        public int PayloadPosition(byte[] datagram)
        {
            //int Upper = datagram[8];
            //int Lower = datagram[9];
            //int _res = (Upper * 255) + (Lower * 1);
            int _res = Decode4BToInt32(datagram[8], datagram[9], datagram[10], datagram[11]);
            return _res;
        }

        //REWRITTEN 2B->4B: 12 13 14 15
        //read |0_DATA|...|10_length_u|11_length_l|...|415|
        public int PayloadLength(byte[] datagram)
        {
            //int Upper = datagram[10];
            //int Lower = datagram[11];
            //int _res = (Upper * 255) + (Lower * 1);
            int _res = Decode4BToInt32(datagram[12], datagram[13], datagram[14], datagram[15]);
            return _res;
        }

        //REWRITTEN 2B->4B: 16 17 18 19
        //read |102_RESEND|...|16_resend_u|17_resend_l|...|415|
        public int PayloadResendID(byte[] datagram)
        {
            //int Upper = datagram[16];
            //int Lower = datagram[17];
            //int _res = (Upper * 255) + (Lower * 1);
            int _res = Decode4BToInt32(datagram[16], datagram[17], datagram[18], datagram[19]); //TODO shift offset in handler PHP
            return _res;
        }


        ////Datagrams For Communication & Misc.
        ///
        //|100_ACK|...|p.415_(NA.NA)|
        public byte[] makeACKDatagram()
        {
            byte[] _ack = new byte[416];

            _ack[0] = (byte)(Action.ACK);
            for (int i = 1; i <= 415; i++)
            {
                _ack[i] = (byte)(NA.NA);
            }
            return _ack;
        }
        //|101_FIN|...|p.415_(NA.NA)|
        public byte[] makeFINDatagram()
        {
            byte[] _fin = new byte[416];

            _fin[0] = (byte)(Action.FIN);
            for (int i = 1; i <= 415; i++)
            {
                _fin[i] = (byte)(NA.NA);
            }
            return _fin;
        }

        //REWRITTEN 2B->4B: 16 17 18 19
        //|102_RESEND|...|p.16_ID_u|p.17_ID_l|...|p.415_(NA.NA)|
        public byte[] makeRESENDDatagram(Int32 id)
        {
            byte[] _resend = new byte[416];

            _resend[0] = (byte)(Action.RESEND);
            for (int i = 1; i <= 15; i++)
            {
                _resend[i] = (byte)(NA.NA);
            }
            byte n16M = (byte)0;
            byte n65k = (byte)0;
            byte n256 = (byte)0;
            byte n1 = (byte)0;
            EncodeInt32NumberTo4B(id, ref n16M, ref n65k, ref n256, ref n1);
            //_resend[16] = (byte)(id / 255);
            //_resend[17] = (byte)(id % 255);
            _resend[16] = (byte)(n16M % 256);
            _resend[17] = (byte)(n65k % 256);
            _resend[18] = (byte)(n256 % 256);
            _resend[19] = (byte)(n1 % 256);
            for (int i = 20; i <= 415; i++)
            {
                _resend[i] = (byte)(NA.NA);
            }
            return _resend;
        }
        //|103_ERR|...|p.415_(NA.NA)|
        public byte[] makeERRDatagram()
        {
            byte[] _err = new byte[416];

            _err[0] = (byte)(Action.ERR);
            for (int i = 1; i <= 415; i++)
            {
                _err[i] = (byte)(NA.NA);
            }
            return _err;
        }
        public void EncodeInt32NumberTo4B(Int32 number, ref byte n16M, ref byte n56k, ref byte n256, ref byte n1)
        {
            Int32 auxiliary = number;
            n16M = (byte)(auxiliary/ 16777216); // 2^24 ~ 16777216
            auxiliary = auxiliary % 16777216;
            n56k = (byte)(auxiliary/65536); //2^16 ~ 65536
            auxiliary = auxiliary % 65536;
            n256 = (byte)(auxiliary/256); //2^8 ~ 256
            auxiliary = auxiliary % 256;
            n1 = (byte)(auxiliary/1); //2^0 ~ 1
        }
        public Int32 Decode4BToInt32(byte n16M, byte n65k, byte n256, byte n1)
        {
            Int32 _result = (n16M * 16777216)+(n65k * 65536)+(n256 * 256)+(n1 * 1);
            return _result;
        }

        //Flush buffer before communicating something
        public void FlushBuffer()
        {
            try
            {   //can read
                if (s.Available > 0)
                {   //while buffered bullshit present
                    while (s.Available > 0)
                    {
                        byte[] disposableBufferNoise = new byte[s.Available];
                        //or new IPEndPoint(IpAddress.Any, 0);
                        IPEndPoint remoteEPnoise = null;
                        disposableBufferNoise = s.Receive(ref remoteEPnoise);
                    }
                }
            }
            catch (Exception e)
            {
                throw new SocketRuntimeError();
            }
        }
        //WARNING: WILL BE 4B THEN ENCODE: FIRSTLARGEST SECOND THIRD  



        ////InApp Content Requests and Inserts from the CMS

        /* public void DummyLogin()
         {
             //dummy user
             dotycny = new User();
             dotycny.Volume = (Int32)0;//(Int32)UserVolume.Full
             dotycny.id = 1;
             dotycny.first_name = "Lukas";
             dotycny.last_name = "Kousal";
             dotycny.email = "mam949@seznam.cz";
             dotycny.approved = true;
             dotycny.rights = 2;
             dotycny.affiliation = 2;
         }*/

        public AuthPack Login(String user, String pass)
        {
            if (String.IsNullOrEmpty(user) || String.IsNullOrEmpty(pass))
            {
                throw new InputError();
                //return null;
            }
            byte[] auth_datagram = new byte[416];
            #region auth_datagram
            auth_datagram[0] = (byte)(Action.LOGIN);
            auth_datagram[1] = (byte)(NA.NA);
            auth_datagram[2] = (byte)(NA.NA);
            auth_datagram[3] = (byte)(NA.NA);
            auth_datagram[4] = (byte)(NA.NA);
            auth_datagram[5] = (byte)(NA.NA);
            auth_datagram[6] = (byte)(NA.NA);
            auth_datagram[7] = (byte)(NA.NA);
            auth_datagram[8] = (byte)(NA.NA);
            auth_datagram[9] = (byte)(NA.NA);
            auth_datagram[10] = (byte)(NA.NA);
            auth_datagram[11] = (byte)(NA.NA);
            auth_datagram[12] = (byte)(NA.NA);
            auth_datagram[13] = (byte)(NA.NA);
            auth_datagram[14] = (byte)(NA.NA);
            auth_datagram[15] = (byte)(NA.NA);
            #endregion
            //Serialize Title And Content From Function Call As {"title":"Article title","content":"Article content"}
            //object[] args = new object[] { title, content };
            //String json = String.Format("\"title\":\"{0}\",\"content\":\"{1}\"", args);
            //json = "{" + json + "}";

            //Encode json to array of bytes
            //byte[] payload = Encoding.ASCII.GetBytes(json);

            object[] args = new object[] { user, pass };
            String credentials = String.Format("\"user\":\"{0}\",\"pass\":\"{1}\"", args);
            credentials = "{" + credentials + "}";
            byte[] payload = Encoding.ASCII.GetBytes(credentials);

            Array.Copy(payload, 0, auth_datagram, 16, payload.Length);

            //send login
            byte[] encrypted_request_datagram = Encryption.Encrypt(auth_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_request_datagram, 416);

            String strToken = "";
            
            //receive token just one
            byte[] respDatagram = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            if (asyncBegin.IsCompleted)
            {
                IPEndPoint remoteEPfirst = null;
                respDatagram = s.EndReceive(asyncBegin, ref remoteEPfirst);
                byte[] respDatagramDecrypted = Encryption.Decrypt(respDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);

                byte[] respDatagramContent = new byte[400];
                Array.Copy(respDatagramDecrypted, 16, respDatagramContent, 0, (respDatagramDecrypted.Length - 16));

                strToken = System.Text.Encoding.UTF8.GetString(respDatagramContent);
                strToken = strToken.Replace("\0", String.Empty);
            }
            else
            {
                //throw new RTE Timeouted
                throw new Timeouted();
            }
            //processing
            try
            {
                var converter = new ExpandoObjectConverter();
                var _authpack = JsonConvert.DeserializeObject<AuthPack>(strToken);
                return _authpack;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            return null;
        }

        //TODO IMPLEMENT SESSION BREAK
        public void InvalidateToken()
        {

            byte[] invTkn_datagram = new byte[416];
            #region auth_datagram
            invTkn_datagram[0] = (byte)(Action.INVTKN);
            invTkn_datagram[1] = (byte)(NA.NA);
            invTkn_datagram[2] = (byte)(NA.NA);
            invTkn_datagram[3] = (byte)(NA.NA);
            invTkn_datagram[4] = (byte)(NA.NA);
            invTkn_datagram[5] = (byte)(NA.NA);
            invTkn_datagram[6] = (byte)(NA.NA);
            invTkn_datagram[7] = (byte)(NA.NA);
            invTkn_datagram[8] = (byte)(NA.NA);
            invTkn_datagram[9] = (byte)(NA.NA);
            invTkn_datagram[10] = (byte)(NA.NA);
            invTkn_datagram[11] = (byte)(NA.NA);
            invTkn_datagram[12] = (byte)(NA.NA);
            invTkn_datagram[13] = (byte)(NA.NA);
            invTkn_datagram[14] = (byte)(NA.NA);
            invTkn_datagram[15] = (byte)(NA.NA);
            #endregion

            //Add token |INVTKN|15xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, invTkn_datagram, 16, tokenBytes.Length);

            //encrypt inv tkn request
            byte[] encrypted_invTkn_datagram = Encryption.Encrypt(invTkn_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);

            //flush it 3 times at server, reply not needed, cuz we're expiring token at server-side anyways
            s.Send(encrypted_invTkn_datagram, 416);
            s.Send(encrypted_invTkn_datagram, 416);
            s.Send(encrypted_invTkn_datagram, 416);

        }

        //REWRITTEN 2B->4B: 4 5 6 7
        //Return Post by id
        public Post ReqPost(Int32 id)
        {
            //int upper = id / 255;
            //int lower = id % 255;
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(id, ref id16M, ref id65k, ref id256, ref id1);

            byte[] request_datagram = new byte[416];
            #region datagram header
            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.AKT);
            request_datagram[2] = (byte)(Quantity.SINGLE);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(id16M); //ID 4TH
            request_datagram[5] = (byte)(id65k); //ID 3RD
            request_datagram[6] = (byte)(id256); //ID 2ND
            request_datagram[7] = (byte)(id1); // ID 1ST
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);
            #endregion

            //Add token |REQ|AKT|SINGLE|NA|upper|lower|...|16+token|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //tryparse the post
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);

            //NewtonSoft deserializer in Post.TryParse
            Post _post = null;
            if (Post.TryParse(_srvr_reply, out _post))
            {
                return _post;
            }
            else
            {
                throw new NewtonSoftDeserializationError();
                //return null;
            }
            //return null;
        }

        //Request Number Of Posts For Editovat Aktuality
        public List<Post> ReqPostsList(Int32 count)
        {
            //int upper = count / 255;
            //int lower = count % 255;
            byte cnt16M = (byte)0;
            byte cnt65k = (byte)0;
            byte cnt256 = (byte)0;
            byte cnt1 = (byte)0;
            EncodeInt32NumberTo4B(count, ref cnt16M, ref cnt65k, ref cnt256, ref cnt1);

            byte[] request_datagram = new byte[416];
            #region datagram header
            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.AKT);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(cnt16M); // CNT 4TH
            request_datagram[5] = (byte)(cnt65k); // CNT 3RD
            request_datagram[6] = (byte)(cnt256); // CNT 2ND
            request_datagram[7] = (byte)(cnt1); // CNT 1ST
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);
            #endregion

            //Add token |REQ|AKT|SINGLE|N/A|4xcnt|4xN/A|4xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //tryparse an array of posts
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            try
            {
                var converter = new ExpandoObjectConverter();
                var _posts = JsonConvert.DeserializeObject<List<Post>>(_srvr_reply);
                return _posts;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            //return null;
        }

        //REWRITTEN Nth/OutOfN 2B->4B,  8 9 10 11 resp. 12 13 14 15
        //Insert Post with Title and Content 
        public bool InsertPost(String title, String content)
        {
            if (String.IsNullOrEmpty(title) || String.IsNullOrEmpty(content))
            {
                throw new InputError();
                return false;
            }

            //Serialize Title And Content From Function Call As {"title":"Article title","content":"Article content"}
            object[] args = new object[] { title, content };
            String json = String.Format("\"title\":\"{0}\",\"content\":\"{1}\"", args);
            json = "{" + json + "}";

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            //int NthUpper = 0;
            //int NthLower = 0;
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0) {
                load += 1;
            }

            //Get number of datagrams encoded into two bytes 
            //int OutOfNUpper = load / 255;
            //int OutOfNLower = load % 255;
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.AKT);
            fdata_datagram[2] = (byte)(Quantity.SINGLE);
            fdata_datagram[3] = (byte)(Handle.CREATE);
            fdata_datagram[4] = (byte)(NA.NA); // ID16M not necessary
            fdata_datagram[5] = (byte)(NA.NA); // ID65k not necessary
            fdata_datagram[6] = (byte)(NA.NA); // ID256 not necessary
            fdata_datagram[7] = (byte)(NA.NA); // ID1 not necessary
            fdata_datagram[8] = (byte)(Nth16M); //     Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //     Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); //    Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //      Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|AKT|SINGLE|CREATE|4xN/A|4xNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Send encrypted FUTURE DATA datagram to server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                        //return false;
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                    //return false;
                }
            }
            else
            {
                throw new Timeouted();
                //return false;
            }
            //No exception was thrown since here so the insert was successful hopefully
            //return true;
        }

        //Update Post
        public bool UpdatePost(Post p)
        {
            //Serialize post for update
            String json = p.SerializeUpdate();

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            //int NthUpper = 0;
            //int NthLower = 0;
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            //int OutOfNUpper = load / 255;
            //int OutOfNLower = load % 255;
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //REWRITTEN 2B->4B
            //Prepare id
            //int id_upper = Convert.ToInt32(p.id) / 255;
            //int id_lower = Convert.ToInt32(p.id) % 255;
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(Convert.ToInt32(p.id), ref id16M, ref id65k, ref id256, ref id1);

            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA); // FUTURE DATA
            fdata_datagram[1] = (byte)(Content.AKT); // AKTUALITA
            fdata_datagram[2] = (byte)(Quantity.SINGLE); // SINGLE
            fdata_datagram[3] = (byte)(Handle.UPDATE); // UPDATE
            fdata_datagram[4] = (byte)(id16M); // id16M # * 16777216
            fdata_datagram[5] = (byte)(id65k); // id65k # * 65536
            fdata_datagram[6] = (byte)(id256); // id256 # * 256
            fdata_datagram[7] = (byte)(id1); //   id1   # * 1
            fdata_datagram[8] = (byte)(Nth16M); //  Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //  Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); // Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //   Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|AKT|SINGLE|UPDATE|4xid|4xNth|4xOutOfN|16+token||...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Send FUTURE DATA datagram to the server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received ACK prolly
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                }
            }
            else
            {
                throw new Timeouted();
            }
        }

        //List of slim Users: [{id, first_name, last_name, -NULL-, -NULL-, -NULL-, affiliation}, ... ]
        public List<User> ReqNotApprovedUsersList()
        {
            byte[] request_datagram = new byte[416];
            #region datagram header
            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.NAUSR);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(NA.NA);
            request_datagram[5] = (byte)(NA.NA);
            request_datagram[6] = (byte)(NA.NA);
            request_datagram[7] = (byte)(NA.NA);
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);
            #endregion

            //Add token |REQ|AKT|SINGLE|N/A|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //Communicate request via network function
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            //TryParse List<Users>
            try
            {
                var converter = new ExpandoObjectConverter();
                var _usrsNA = JsonConvert.DeserializeObject<List<User>>(_srvr_reply);
                return _usrsNA;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            //return null;
        }

        //Approve newly registered user
        public bool ApproveUser(Int32 id)
        {
            //int upper = id / 255;
            //int lower = id % 255;
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(id, ref id16M, ref id65k, ref id256, ref id1);

            byte[] approve_user_datagram = new byte[416];

            approve_user_datagram[0] = (byte)(Action.APV);
            for (int i = 1; i <= 15; i++)
            {
                approve_user_datagram[i] = (byte)(NA.NA);
            }
            //Rewrite 2B->4B and PHP edit pls!
            approve_user_datagram[16] = (byte)(id16M);
            approve_user_datagram[17] = (byte)(id65k);
            approve_user_datagram[18] = (byte)(id256);
            approve_user_datagram[19] = (byte)(id1);

            //Add token |APV|N/A|N/A|N/A|8xN/A||16+17+18+19 id|20-30 token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            //REWRITTEN 18->20 offset
            Array.Copy(tokenBytes, 0, approve_user_datagram, 20, tokenBytes.Length);


            byte[] encrypted_approve_user_datagram = Encryption.Encrypt(approve_user_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_approve_user_datagram, 416);

            byte[] responseDatagram = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            if (asyncBegin.IsCompleted)
            {
                IPEndPoint remoteEPfirst = null;
                responseDatagram = s.EndReceive(asyncBegin, ref remoteEPfirst);
                byte[] responseDatagramDecrypted = Encryption.Decrypt(responseDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                //It's FIN or ERR
                if (responseDatagramDecrypted[0] == (byte)(Action.FIN))
                {
                    //nice FIN
                    return true;
                }
                else if (responseDatagramDecrypted[0] == (byte)(Action.ERR))
                {
                    //dafaq ERR but whatever
                    return false;
                }
                else
                {
                    throw new ProtocolFlowError();
                }
            }
            else
            {
                throw new Timeouted();
            }
        }

        //Request List of upcoming slim Cups: [{id, date, name, -NULL-, -NULL-}, ... ]
        public List<Cup> ReqCupsList()
        {
            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.ZAV);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(NA.NA); //upper
            request_datagram[5] = (byte)(NA.NA); //lower
            request_datagram[6] = (byte)(NA.NA);
            request_datagram[7] = (byte)(NA.NA);
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);
 

            //Add token |REQ|AKT|SINGLE|N/A|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //Communicate request via network function
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            //TryParse List<Cup>
            try
            {
                var converter = new ExpandoObjectConverter();
                List<Cup> _cups = null;
                _cups = JsonConvert.DeserializeObject<List<Cup>>(_srvr_reply);
                return _cups;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
        }

        //REWRITTEN 2B->4B ID
        //Request pairing for this cup, 
        public void ReqPairing(Int32 id, out List<Position> positions, out List<User> registered, out List<User> nametags, out List<PairIDPozIDUser> idpoz_iduser)
        {
            //Encode cupID to be sent
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(id, ref id16M, ref id65k, ref id256, ref id1);

            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.ZAV);
            request_datagram[2] = (byte)(Quantity.SINGLE);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(id16M); // id 4th
            request_datagram[5] = (byte)(id65k); // id 3rd
            request_datagram[6] = (byte)(id256); // id 2nd
            request_datagram[7] = (byte)(id1); // id 1st
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);


            //Add token |REQ|ZAV|SINGLE|N/A|4xid|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //req the bloody JSONs via wrapper
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            String[] JSONs = _srvr_reply.Split(';');
            if (JSONs.Length == 4)
            {
                var converter = new ExpandoObjectConverter();
                try
                {
                    var _positions = JsonConvert.DeserializeObject<List<Position>>(JSONs[0]);
                    var _registered = JsonConvert.DeserializeObject<List<User>>(JSONs[1]);
                    var _nametags = JsonConvert.DeserializeObject<List<User>>(JSONs[2]);
                    var _idpoz_iduser = JsonConvert.DeserializeObject<List<PairIDPozIDUser>>(JSONs[3]);
                    positions = _positions;
                    registered = _registered;
                    nametags = _nametags;
                    idpoz_iduser = _idpoz_iduser;
                }
                catch (Exception e)
                {
                    throw new NewtonSoftDeserializationError();
                }
            }
            else
            {
                throw new JSONsSplitError();
            }
            //return null;
            //response POSITIONS;REGISTERED;IDPOZUSER
            //out positions, out registered, out idpoz_iduser
            //out of this function set the interface - DONE
        }

        //REWRITTEN 2B->4B and positions ofc
        //FDATA Update pairing
        public bool UpdatePairing(Int32 cupID, String json)
        {
            //Encode cupID to be sent
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(cupID, ref id16M, ref id65k, ref id256, ref id1);

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.PAIRINGPZ);
            fdata_datagram[2] = (byte)(Quantity.LISTING);
            fdata_datagram[3] = (byte)(Handle.UPDATE);
            fdata_datagram[4] = (byte)(id16M); // cup id16M # * 16777216
            fdata_datagram[5] = (byte)(id65k); // cup id65k # * 65536
            fdata_datagram[6] = (byte)(id256); // cup id256 # * 256
            fdata_datagram[7] = (byte)(id1); //   cup id1     # * 1
            fdata_datagram[8] = (byte)(Nth16M); //     Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //     Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); //    Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //      Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|PAIRINGPZ|LISTING|UPDATE|4xid|4zNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Ecrypt and Send FUTURE DATA datagram to server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait for ACK
            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                        //return false;
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                    //return false;
                }
            }
            else
            {
                throw new Timeouted();
                //return false;
            }
            //No exception was thrown until here so the insert was successful hopefully
        }

        //Maybe keep for retrieving positions? Is wired on server?
        //DEPRECATE REQ, NOT TESTED, DEPRECATE THIS SHIT PLS
        public List<Position> ReqPositions()
        {
            byte[] request_datagram = new byte[416];
            #region datagram header
            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.POSITIONS);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(Handle.DESC);
            request_datagram[4] = (byte)(NA.NA);
            request_datagram[5] = (byte)(NA.NA);
            request_datagram[6] = (byte)(NA.NA);
            request_datagram[7] = (byte)(NA.NA);
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);
            #endregion
            #region datagram nullbody
            /*for (int i = 16; i <= 415; i++) {
                request_datagram[i] = (byte)(NA.NA);
            }*/
            #endregion

            byte[] encrypted_request_datagram = Encryption.Encrypt(request_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_request_datagram, 416);

            byte[] firstDatagram = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            if (asyncBegin.IsCompleted)
            {
                IPEndPoint remoteEPfirst = null;
                firstDatagram = s.EndReceive(asyncBegin, ref remoteEPfirst);
                byte[] firstDatagramDecrypted = Encryption.Decrypt(firstDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                int N = PayloadLength(firstDatagramDecrypted);
                SortedList<int, string> delivery = new SortedList<int, string>();
                //processing pivoting datagram
                byte[] firstDatagramContent = new byte[400];
                Array.Copy(firstDatagramDecrypted, 16, firstDatagramContent, 0, (firstDatagramDecrypted.Length - 16));
                String strContentOfFirstMessage = System.Text.Encoding.UTF8.GetString(firstDatagramContent);
                strContentOfFirstMessage = strContentOfFirstMessage.Replace("\0", String.Empty);
                delivery.Add(1, strContentOfFirstMessage);
                if (N == 1)
                {
                    //delivery.Add(1, strContentOfFirstMessage);
                }
                else
                {
                    //read rest
                    for (int i = 1; i < N; i++)
                    {
                        byte[] datagram = new byte[416];
                        var asyncFollowUp = s.BeginReceive(null, null);
                        asyncFollowUp.AsyncWaitHandle.WaitOne(OneSecond);
                        if (asyncFollowUp.IsCompleted)
                        {
                            try
                            {
                                IPEndPoint remoteEP = null;
                                datagram = s.EndReceive(asyncFollowUp, ref remoteEP);
                                byte[] datagramDecrypted = Encryption.Decrypt(datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                int ith = PayloadPosition(datagramDecrypted);
                                byte[] datagramContent = new byte[400];
                                Array.Copy(datagramDecrypted, 16, datagramContent, 0, (datagramDecrypted.Length - 16));
                                String strContentOfMessage = System.Text.Encoding.UTF8.GetString(datagramContent);
                                strContentOfMessage = strContentOfMessage.Replace("\0", String.Empty);
                                delivery.Add(ith, strContentOfMessage);
                                //return null;
                            }
                            catch (Exception e)
                            {
                                //return null;
                            }
                        }
                        else
                        {
                            //return null;
                        }
                    }

                    //check completness of payload
                    bool[] firstCheck = new bool[N + 1];
                    for (int i = 1; i <= (N); i++)
                        firstCheck[i] = false;
                    foreach (KeyValuePair<int, string> part in delivery)
                    {
                        firstCheck[part.Key] = true;
                    }
                    List<int> firstMissing = new List<int>();
                    for (int i = 1; i <= N; i++)
                    {
                        if (firstCheck[i] == false)
                            firstMissing.Add(i);
                    }
                    //request again if necessary
                    if (firstMissing.Count > 0)
                    {
                        int firstMissingN = firstMissing.Count;
                        foreach (Int32 missingith in firstMissing)
                        {
                            byte[] req = makeRESENDDatagram(missingith);
                            byte[] encryptedReq = Encryption.Encrypt(req, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                            s.Send(encryptedReq, 416);
                            byte[] resentDatagram = new byte[416];
                            var asyncResent = s.BeginReceive(null, null);
                            asyncResent.AsyncWaitHandle.WaitOne(HalfOfSecond);
                            if (asyncResent.IsCompleted)
                            {
                                try
                                {
                                    IPEndPoint remoteEPResent = null;
                                    resentDatagram = s.EndReceive(asyncResent, ref remoteEPResent);
                                    byte[] resentDatagramDecrypted = Encryption.Decrypt(resentDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                    int ith = PayloadPosition(resentDatagramDecrypted);
                                    byte[] resentDatagramContent = new byte[400];
                                    Array.Copy(resentDatagramDecrypted, 16, resentDatagramContent, 0, (resentDatagramDecrypted.Length - 16));
                                    String strContentOfResentMessage = System.Text.Encoding.UTF8.GetString(resentDatagramContent);
                                    strContentOfResentMessage = strContentOfResentMessage.Replace("\0", String.Empty);
                                    delivery.Add(ith, strContentOfResentMessage);
                                }
                                catch (Exception e)
                                {
                                    //ugh wtf
                                }
                            }
                            else
                            {
                                //ugh nvm
                            }
                        }
                    }
                    else
                    {
                        //good, nothing is missing
                    }
                    //doreq
                }
                //close
                byte[] fin = makeFINDatagram();
                byte[] encryptedFin = Encryption.Encrypt(fin, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                s.Send(encryptedFin, 416);
                String txtString = "";
                foreach (var part in delivery)
                {
                    txtString += part.Value;
                }
                List<Position> positions;
                var converter = new ExpandoObjectConverter();
                try
                {
                    var _positions = JsonConvert.DeserializeObject<List<Position>>(txtString);
                    positions = _positions;
                    return positions;
                }
                catch (Exception e)
                {
                    throw new NewtonSoftDeserializationError();
                }
            }
            else
            {
                throw new Timeouted();
                //return null;
            }
            return null;
        }
        

        //REQ List of Clubs
        public List<Club> ReqClubs()
        {
            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.CLUB);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(NA.NA);
            request_datagram[5] = (byte)(NA.NA);
            request_datagram[6] = (byte)(NA.NA);
            request_datagram[7] = (byte)(NA.NA);
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);

            //Add token |REQ|CLUB|LISTING|N/A|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            //req clubs, deserialize and return
            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            List<Club> clubs;
            var converter = new ExpandoObjectConverter();
            //TryParse List<Club>
            try
            {
                var _clubs = JsonConvert.DeserializeObject<List<Club>>(_srvr_reply);
                clubs = _clubs;
                return clubs;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            //return null;
        }

        //REWRITTEN NUMBERS 2B->4B Nth OutOfN
        //VedouciKlubu page FDATA+content
        public bool InsertCup(String name, String date, String club, String content)
        {
            if (String.IsNullOrEmpty(name) || String.IsNullOrEmpty(date)|| String.IsNullOrEmpty(club)|| String.IsNullOrEmpty(content))
            {
                throw new InputError();
            }

            //Serialize Name, Date, Club and Content From Function Call As {"name":"Cup name","date":"2019-03-31","club":"2","content":"Popis toho zavodu je zde"}
            object[] args = new object[] {name, date, club, content};
            String json = String.Format("\"name\":\"{0}\",\"date\":\"{1}\",\"club\":\"{2}\",\"content\":\"{3}\"", args);
            json = "{" + json + "}";

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.ZAV);
            fdata_datagram[2] = (byte)(Quantity.SINGLE);
            fdata_datagram[3] = (byte)(Handle.CREATE);
            fdata_datagram[4] = (byte)(NA.NA); // N/A
            fdata_datagram[5] = (byte)(NA.NA); // N/A
            fdata_datagram[6] = (byte)(NA.NA); // N/A
            fdata_datagram[7] = (byte)(NA.NA); // N/a
            fdata_datagram[8] = (byte)(Nth16M); //     Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //     Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); //    Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //      Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|ZAV|SINGLE|CREATE|4xN/A|4xNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Send FUTURE DATA datagram to server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                }
            }
            else
            {
                throw new Timeouted();
            }
            //No exception was thrown since here so the insert was successful hopefully
        }

        //VedouciKlubu page Req my teammates - RELEASE
        public List<User> ReqClubFriends()
        {
            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.CLUBFRIENDS);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(NA.NA);
            request_datagram[5] = (byte)(NA.NA);
            request_datagram[6] = (byte)(NA.NA);
            request_datagram[7] = (byte)(NA.NA);
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);


            //Add token |REQ|CLUBFRIENDS|LISTING|N/A|4xN/A|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            //TryParse List<User>
            try
            {
                var converter = new ExpandoObjectConverter();
                var _friends = JsonConvert.DeserializeObject<List<User>>(_srvr_reply);
                return _friends;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            //return null;
        }

        //CHANGE cupID 2B->4B
        //VedouciKlubu page Req registered teammates for this cup RELEASE
        public List<User> ReqClubFriendsForCup(Int32 cupID)
        {
            //Encode cupID to be sent
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(cupID, ref id16M, ref id65k, ref id256, ref id1);

            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.CLUBFRIENDSFORCUP);
            request_datagram[2] = (byte)(Quantity.LISTING);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(id16M); // cupID 4th 
            request_datagram[5] = (byte)(id65k); // cupID 3rd
            request_datagram[6] = (byte)(id256); // cupID 2nd
            request_datagram[7] = (byte)(id1);   // cupID 1st
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);

            //Add token |REQ|CLUBFRIENDSFORCUP|LISTING|N/A|4xcupID|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);

            try
            {
                var converter = new ExpandoObjectConverter();
                var _friendsForCup = JsonConvert.DeserializeObject<List<User>>(_srvr_reply);
                return _friendsForCup;
            }
            catch (Exception e)
            {
                throw new NewtonSoftDeserializationError();
            }
            //return null;
        }

        //TI - 2nd page Update teammates for the cup
        public bool UpdateAvailability(Int32 cupID, String JSON)
        {
            //Encode cupID to be sent
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(cupID, ref id16M, ref id65k, ref id256, ref id1);

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(JSON);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.PAIRINGRZ);
            fdata_datagram[2] = (byte)(Quantity.LISTING);
            fdata_datagram[3] = (byte)(Handle.UPDATE);
            fdata_datagram[4] = (byte)(id16M); // cup id16M # * 16777216
            fdata_datagram[5] = (byte)(id65k); // cup id65k # * 65536
            fdata_datagram[6] = (byte)(id256); // cup id256 # * 256
            fdata_datagram[7] = (byte)(id1); //   cup id1   # * 1
            fdata_datagram[8] = (byte)(Nth16M); //     Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //     Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); //    Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //      Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|PAIRINGRZ|LISTING|UPDATE|4xcupID|4xNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Ecrypt and Send FUTURE DATA datagram to server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait for ACK
            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                }
            }
            else
            {
                throw new Timeouted();
            }
            //No exception was thrown until here so the insert was successful hopefully
        }

        //CHANGE cupID 2B->4B
        //Rozhodci Am I registered for this cup?
        public bool ReqAmIForCup(Int32 cupID)
        {
            //Encode cupID to be sent
            byte id16M = (byte)0;
            byte id65k = (byte)0;
            byte id256 = (byte)0;
            byte id1 = (byte)0;
            EncodeInt32NumberTo4B(cupID, ref id16M, ref id65k, ref id256, ref id1);

            byte[] request_datagram = new byte[416];

            request_datagram[0] = (byte)(Action.REQ);
            request_datagram[1] = (byte)(Content.MEFORTHECUP);
            request_datagram[2] = (byte)(NA.NA);
            request_datagram[3] = (byte)(NA.NA);
            request_datagram[4] = (byte)(id16M); // cupID 4th
            request_datagram[5] = (byte)(id65k); // cupID 3rd
            request_datagram[6] = (byte)(id256); // cupID 2nd
            request_datagram[7] = (byte)(id1); // cupID 1st
            request_datagram[8] = (byte)(NA.NA);
            request_datagram[9] = (byte)(NA.NA);
            request_datagram[10] = (byte)(NA.NA);
            request_datagram[11] = (byte)(NA.NA);
            request_datagram[12] = (byte)(NA.NA);
            request_datagram[13] = (byte)(NA.NA);
            request_datagram[14] = (byte)(NA.NA);
            request_datagram[15] = (byte)(NA.NA);


            //Add token |REQ|MEFORTHECUP|NA|NA|4xcupID|8xN/A||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, request_datagram, 16, tokenBytes.Length);

            String _srvr_reply = "";
            _srvr_reply = CommunicateReq(request_datagram);
            try
            {
                var converter = new ExpandoObjectConverter();
                Reply reply = JsonConvert.DeserializeObject<Reply>(_srvr_reply);
                if (reply.IsTrue())
                {
                    return true;
                }
                else if (!reply.IsTrue())
                {
                    return false;
                }
                else
                {
                    throw new ReplyAnswerException();
                }
            }
            catch (Exception ex)
            {
                throw new NewtonSoftDeserializationError();
            }
        }

        //CHANGE 2B->4B Nth OutOfN: 8 9 10 11, 12 13 14 15
        //Rozhodci - 2nd page Register me for this cup
        public bool RegisterForTheCup(Int32 cupID, Int32 userID)
        {
            //Create Registration object
            PairIDCupIDUser registration = new PairIDCupIDUser(cupID, userID);

            //Serialize Registration to json
            String json = registration.Serialize();

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.MEFORTHECUP);
            fdata_datagram[2] = (byte)(Quantity.SINGLE);
            fdata_datagram[3] = (byte)(Handle.CREATE);
            fdata_datagram[4] = (byte)(NA.NA);
            fdata_datagram[5] = (byte)(NA.NA); 
            fdata_datagram[6] = (byte)(NA.NA);
            fdata_datagram[7] = (byte)(NA.NA);
            fdata_datagram[8] = (byte)(Nth16M); //  Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //  Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); // Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //   Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|MEFORCUP|SINGLE|CREATE|4xN/A|4xNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //Send FUTURE DATA datagram to the server
            byte[] encrypted_fdata_datagram = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata_datagram, 416);

            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received ACK prolly
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                }
            }
            else
            {
                throw new Timeouted();
            }
            //return true;
        }

        //CHANGE 2B-> Nth OutOfN: 8 9 10 11, 12 13 14 15
        //SuperUser - register user to administration
        public bool RegisterUser(String first_name, String last_name, String email, String password, String prava, String klub)
        {
            //Throws serialization error
            object[] args = new object[] { first_name, last_name, email, password, prava, klub };
            String json = String.Format("\"first_name\":\"{0}\",\"last_name\":\"{1}\",\"email\":\"{2}\",\"password\":\"{3}\",\"prava\":\"{4}\",\"klub\":\"{5}\"", args);
            json = "{" + json + "}";

            //Encode json to array of bytes
            byte[] payload = Encoding.ASCII.GetBytes(json);

            //Set Nth16M, Nth65k, Nth256, Nth1 to 0, because this is the first datagram
            byte Nth16M = (byte)0;
            byte Nth65k = (byte)0;
            byte Nth256 = (byte)0;
            byte Nth1 = (byte)0;
            EncodeInt32NumberTo4B((int)0, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);

            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }

            //Get number of datagrams encoded into four bytes 
            byte OutOfN16M = (byte)0;
            byte OutOfN65k = (byte)0;
            byte OutOfN256 = (byte)0;
            byte OutOfN1 = (byte)0;
            EncodeInt32NumberTo4B(load, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

            //Prepare datagram FUTURE DATA to get server ready for content which is going to be sent
            byte[] fdata_datagram = new byte[416];
            //Header part
            fdata_datagram[0] = (byte)(Action.FDATA);
            fdata_datagram[1] = (byte)(Content.USR);
            fdata_datagram[2] = (byte)(Quantity.SINGLE);
            fdata_datagram[3] = (byte)(Handle.CREATE);
            fdata_datagram[4] = (byte)(NA.NA); // id_upper position NOT NECESSARY in this case
            fdata_datagram[5] = (byte)(NA.NA); // id_lower position NOT NECESSARY in this case
            fdata_datagram[6] = (byte)(NA.NA); // blank
            fdata_datagram[7] = (byte)(NA.NA); // blank
            fdata_datagram[8] = (byte)(Nth16M); //  Nth16M 0    
            fdata_datagram[9] = (byte)(Nth65k); //  Nth65k 0
            fdata_datagram[10] = (byte)(Nth256); // Nth256 0
            fdata_datagram[11] = (byte)(Nth1); //   Nth1   0
            fdata_datagram[12] = (byte)(OutOfN16M); // OutOfN16M # * 16777216
            fdata_datagram[13] = (byte)(OutOfN65k); // OutOfN65k # * 65536
            fdata_datagram[14] = (byte)(OutOfN256); // OutOfN256 # * 256
            fdata_datagram[15] = (byte)(OutOfN1); //   OutOfN1   # * 1

            //Add token |FDATA|USR|SINGLE|CREATE|4xN/A|4xNth|4xOutOfN||16+token|...|
            String token = this.authpack.SerializeAuthTkn();
            byte[] tokenBytes = Encoding.ASCII.GetBytes(token);

            Array.Copy(tokenBytes, 0, fdata_datagram, 16, tokenBytes.Length);

            //cut here?
            //Ecrypt and Send FUTURE DATA datagram to server
            byte[] encrypted_fdata = Encryption.Encrypt(fdata_datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_fdata, 416);

            //Wait for ACK
            //Wait ≤ one second for ACK datagram from server or timeout
            byte[] firstACK = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            //Received
            if (asyncBegin.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEPfirst = null;
                    firstACK = s.EndReceive(asyncBegin, ref remoteEPfirst);
                    byte[] firstACKDecrypted = Encryption.Decrypt(firstACK, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    //It's ACK datagram
                    if (firstACKDecrypted[0] == (byte)(Action.ACK))
                    {
                        bool _res;
                        _res = CommunicateData(payload);
                        return _res;
                        #region old_networking
                        /*
                        //Our request was registered on server; Prepare dictionary for datagrams to be sent in the form of pos->datagram
                        Dictionary<int, byte[]> datagrams = new Dictionary<int, byte[]>();
                        //Case of only one content datagram to be sent
                        if (payload.Length <= 400)
                        {
                            //This DATA datagram is going to be just 1/1
                            //Header part
                            byte[] send = new byte[416];
                            send[0] = (byte)(Action.DATA);
                            for (int i = 1; i <= 7; i++)
                                send[i] = (byte)(NA.NA);
                            send[8] = (byte)(0);
                            send[9] = (byte)(1);
                            send[10] = (byte)(0);
                            send[11] = (byte)(1);
                            for (int i = 12; i <= 15; i++)
                                send[i] = (byte)(NA.NA);
                            //Payload part
                            Array.Copy(payload, 0, send, 16, payload.Length);

                            //Encrypt prepared datagram
                            byte[] sendEncrypted = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                            //Add to 1->datagram dictionary since we have only 1 datagram tbs
                            datagrams.Add(1, sendEncrypted);
                        }
                        //Case of more than one content datagrams to be sent
                        else
                        {
                            //Payload >400+ cut to datagrams like |DATA 400|rest... until it's possible
                            int ith = 1;
                            int outOfN = (payload.Length / 400) + 1;
                            while (payload.Length > 400)
                            {
                                //DATA datagram prepataion
                                byte[] send = new byte[416];
                                //Header part
                                send[0] = (byte)(Action.DATA);
                                for (int i = 1; i <= 7; i++)
                                {
                                    send[i] = (byte)(NA.NA);
                                }
                                send[8] = (byte)(ith / 255); //upper
                                send[9] = (byte)(ith % 255); //lower
                                send[10] = (byte)(outOfN / 255); //upper
                                send[11] = (byte)(outOfN % 255); //lower
                                for (int i = 12; i <= 15; i++)
                                    send[i] = (byte)(NA.NA);
                                //Payload part 
                                //Copy first 400 bytes from whole payload
                                Array.Copy(payload, 0, send, 16, 400);

                                //Cut these first 400 bytes from the whole payload out
                                byte[] truncatedPayload = payload.Skip(400).ToArray();
                                payload = truncatedPayload;

                                //Encrypt prepared datagram
                                byte[] sendEncrypted = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                //Add to dictionary as current i->datagram
                                datagrams.Add(ith, sendEncrypted);
                                //next iteration index
                                ith++;
                            }

                            //Remainder number of bytes from % 400
                            if (payload.Length != 0)
                            {
                                //Last DATA datagram
                                byte[] send = new byte[416];
                                //Header part
                                send[0] = (byte)(Action.DATA);
                                for (int i = 1; i <= 7; i++)
                                {
                                    send[i] = (byte)(NA.NA);
                                }
                                send[8] = (byte)(ith / 255); //upper
                                send[9] = (byte)(ith % 255); //lower
                                send[10] = (byte)(outOfN / 255); //upper
                                send[11] = (byte)(outOfN % 255); //lower
                                for (int i = 12; i <= 15; i++)
                                {
                                    send[i] = (byte)(NA.NA);
                                }
                                send[12] = (byte)(NA.NA);
                                send[13] = (byte)(NA.NA);
                                send[14] = (byte)(NA.NA);
                                send[15] = (byte)(NA.NA);
                                //Payload part 
                                Array.Copy(payload, 0, send, 16, payload.Length);

                                //Encrypt prepared datagram
                                byte[] sendEncrypted = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                //Add to dictionary as last i->datagram
                                datagrams.Add(ith, sendEncrypted);

                                //Not necessary
                                //ith++;
                            }
                            //Payload serialized and prepared to be sent
                        }

                        //Send all parts of payload to server
                        foreach (KeyValuePair<int, byte[]> d in datagrams)
                        {
                            s.Send(d.Value, 416);
                        }

                        //NOT SURE IF THIS WORKS
                        //Wait max two seconds for ACK or RESEND to terminate or proceed
                        byte[] reReq = new byte[416];
                        var asyncReReq = s.BeginReceive(null, null);
                        asyncReReq.AsyncWaitHandle.WaitOne(TwoSeconds);
                        if (asyncReReq.IsCompleted)
                        {
                            try
                            {
                                IPEndPoint remoteEP = null;
                                reReq = s.EndReceive(asyncReReq, ref remoteEP);
                                byte[] reReqDecrypted = Encryption.Decrypt(reReq, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                if (reReqDecrypted[0] == (byte)(Action.ACK))
                                {
                                    //Nice Success
                                    return true;
                                }
                                else if (reReqDecrypted[0] == (byte)(Action.ERR))
                                {
                                    throw new ServerInsertFailure();
                                    return false;
                                }
                                else if (reReqDecrypted[0] == (byte)(Action.RESEND))
                                {
                                    //Count Up To 2x missing
                                    int attempt = 1;
                                    int attempts = 2 * ((OutOfNUpper * 255) + (OutOfNLower * 1));

                                    //Change Reference To next
                                    byte[] next = reReqDecrypted;

                                    //copy array evaluated
                                    //make endpoints
                                    //do loop

                                    //While we're not getting ACK 

                                    //WHAT IF 7/12 STOP RECEIVING EVERYTHING
                                    while ((next[0] != (byte)(Action.ACK)) || (attempt < attempts))
                                    {
                                        //We've Not Exceeded Number Of Attempts
                                        if (attempt < attempts)
                                        {
                                            if (next[0] == (byte)(Action.RESEND))
                                            {
                                                //Resend
                                                int r = PayloadResendID(next);
                                                s.Send(datagrams[r], 416);
                                                //Recv Next
                                                next = null;
                                                next = new byte[416];

                                                var recvNext = s.BeginReceive(null, null);
                                                recvNext.AsyncWaitHandle.WaitOne(HalfOfSecond);
                                                //Received
                                                if (asyncBegin.IsCompleted)
                                                {
                                                    try
                                                    {
                                                        IPEndPoint remoteEPNext = null;
                                                        byte[] nextEncrypted = s.EndReceive(asyncBegin, ref remoteEPNext);
                                                        next = Encryption.Decrypt(nextEncrypted, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                                        //Just Receive And Let The Loop Proceed To Evaluation In Header
                                                        //↓
                                                    }
                                                    catch (Exception ex)
                                                    {
                                                        throw new SocketRuntimeError();
                                                        return false;
                                                    }
                                                }
                                                //Not Received And Not Complete Payload Still Fragmented
                                                else
                                                {
                                                    throw new DeliveryFragmentation();
                                                }
                                                //Increment Attempt Counter
                                                attempt++;
                                            }
                                            else
                                            {
                                                throw new ProtocolFlowError();
                                                return false;
                                            }
                                        }
                                        //We Have Exceeded Number Of Attempts
                                        else
                                        {
                                            break;
                                        }
                                    }
                                    if (attempt == attempts)
                                    {
                                        throw new DeliveryFragmentation();
                                        return false;
                                    }
                                    else
                                    {
                                        //SUCC
                                        return true;
                                    }
                                }
                                else
                                {
                                    throw new ProtocolFlowError();
                                    return false;
                                }
                            }
                            catch (Exception e)
                            {
                                throw new SocketRuntimeError();
                                return false;
                            }
                        }
                        else
                        {
                            throw new Timeouted();
                            return false;
                        }
                        //RESEND slash ACK Ending
                        */
                        #endregion
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                        //return false;
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                    //return false;
                }
            }
            else
            {
                throw new Timeouted();
                //return false;
            }
            //No exception was thrown since here so the insert was successful hopefully
            return true;
        }

        //Handle Req
        //Function to handle network communication within Req functions
        //„Požádej data a případně doptej ty datagramy co nepřišly“
        public String CommunicateReq(byte[] headerReq)
        {
            byte[] encrypted_request_datagram = Encryption.Encrypt(headerReq, this.EncryptionKeyDefault, this.EncryptionIVDefault);
            s.Send(encrypted_request_datagram, 416);

            byte[] firstDatagram = new byte[416];
            var asyncBegin = s.BeginReceive(null, null);
            asyncBegin.AsyncWaitHandle.WaitOne(OneSecond);
            if (asyncBegin.IsCompleted)
            {
                IPEndPoint remoteEPfirst = null;
                firstDatagram = s.EndReceive(asyncBegin, ref remoteEPfirst);
                byte[] firstDatagramDecrypted = Encryption.Decrypt(firstDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                int N = PayloadLength(firstDatagramDecrypted);
                SortedList<int, string> delivery = new SortedList<int, string>();
                //processing pivoting datagram
                byte[] firstDatagramContent = new byte[400];
                Array.Copy(firstDatagramDecrypted, 16, firstDatagramContent, 0, (firstDatagramDecrypted.Length - 16));
                String strContentOfFirstMessage = System.Text.Encoding.UTF8.GetString(firstDatagramContent);
                strContentOfFirstMessage = strContentOfFirstMessage.Replace("\0", String.Empty);
                delivery.Add(1, strContentOfFirstMessage);
                if (N == 1)
                {
                    //already added first one, here
                    //delivery.Add(1, strContentOfFirstMessage);
                }
                else
                {
                    //read rest
                    for (int i = 1; i < N; i++)
                    {
                        byte[] datagram = new byte[416];
                        var asyncFollowUp = s.BeginReceive(null, null);
                        asyncFollowUp.AsyncWaitHandle.WaitOne(OneSecond);
                        if (asyncFollowUp.IsCompleted)
                        {
                            try
                            {
                                IPEndPoint remoteEP = null;
                                datagram = s.EndReceive(asyncFollowUp, ref remoteEP);
                                byte[] datagramDecrypted = Encryption.Decrypt(datagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                int ith = PayloadPosition(datagramDecrypted);
                                byte[] datagramContent = new byte[400];
                                Array.Copy(datagramDecrypted, 16, datagramContent, 0, (datagramDecrypted.Length - 16));
                                String strContentOfMessage = System.Text.Encoding.UTF8.GetString(datagramContent);
                                strContentOfMessage = strContentOfMessage.Replace("\0", String.Empty);
                                delivery.Add(ith, strContentOfMessage);
                            }
                            catch (Exception e)
                            {
                                return null;
                            }
                        }
                        else
                        {
                            return null;
                            //return null;
                        }
                    }

                    //Check completeness of payload
                    bool[] firstCheck = new bool[N + 1];
                    for (int i = 1; i <= (N); i++)
                        firstCheck[i] = false;
                    foreach (KeyValuePair<int, string> part in delivery)
                    {
                        firstCheck[part.Key] = true;
                    }
                    List<int> firstMissing = new List<int>();
                    for (int i = 1; i <= N; i++)
                    {
                        if (firstCheck[i] == false)
                            firstMissing.Add(i);
                    }
                    //Request again if necessary
                    if (firstMissing.Count > 0)
                    {
                        int firstMissingN = firstMissing.Count;
                        foreach (Int32 missingith in firstMissing)
                        {
                            byte[] req = makeRESENDDatagram(missingith);
                            byte[] encryptedReq = Encryption.Encrypt(req, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                            s.Send(encryptedReq, 416);
                            byte[] resentDatagram = new byte[416];
                            var asyncResent = s.BeginReceive(null, null);
                            asyncResent.AsyncWaitHandle.WaitOne(HalfOfSecond);
                            if (asyncResent.IsCompleted)
                            {
                                try
                                {
                                    IPEndPoint remoteEPResent = null;
                                    resentDatagram = s.EndReceive(asyncResent, ref remoteEPResent);
                                    byte[] resentDatagramDecrypted = Encryption.Decrypt(resentDatagram, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                                    int ith = PayloadPosition(resentDatagramDecrypted);
                                    byte[] resentDatagramContent = new byte[400];
                                    Array.Copy(resentDatagramDecrypted, 16, resentDatagramContent, 0, (resentDatagramDecrypted.Length - 16));
                                    String strContentOfResentMessage = System.Text.Encoding.UTF8.GetString(resentDatagramContent);
                                    strContentOfResentMessage = strContentOfResentMessage.Replace("\0", String.Empty);
                                    delivery.Add(ith, strContentOfResentMessage);
                                }
                                catch (Exception e)
                                {
                                    return null;
                                    //lost but don't terminate
                                    //ugh wtf
                                }
                            }
                            else
                            {
                                //lost, but don't terminate
                                //ugh nvm
                            }
                        }
                    }
                    else
                    {
                        //good, nothing is missing
                    }
                    //doreq
                }
                //close
                byte[] fin = makeFINDatagram();
                byte[] encryptedFin = Encryption.Encrypt(fin, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                s.Send(encryptedFin, 416);
                String txtString = "";
                foreach (var part in delivery)
                {
                    txtString += part.Value;
                }
                return txtString;
            }
            else
            {
                throw new Timeouted();
            }
            //return null;
        }
        
        //Handle Data
        //Function to handle network communication within Data functions
        //„Pošli data a případně znovu pošli datagramy co nedorazily“
        public bool CommunicateData(byte[] payload)
        {
            //Calculate ∑ of datagrams necessary to accomodate this payload
            int load = payload.Length / 400;
            if ((payload.Length % 400) > 0)
            {
                load += 1;
            }
            //Our request was registered on server, i.e. FDATA ACK received, proceeding; Prepare dictionary for datagrams to be sent in the form of position->datagram
            Dictionary<int, byte[]> datagrams = new Dictionary<int, byte[]>();
            //Case of only one content datagram to be sent
            if (payload.Length <= 400)
            {
                //This DATA datagram is going to be just 1/1

                //Set Nth16M, Nth65k, Nth256, Nth1 to 1, because this is 1/1
                byte Nth16M = (byte)0;
                byte Nth65k = (byte)0;
                byte Nth256 = (byte)0;
                byte Nth1 = (byte)0;
                EncodeInt32NumberTo4B((int)1, ref Nth16M, ref Nth65k, ref Nth256, ref Nth1);
                
                //Set OutOfN16M, OutOfN65k, OutOfN256, OutOfN1 to 1, because this is 1/1
                byte OutOfN16M = (byte)0;
                byte OutOfN65k = (byte)0;
                byte OutOfN256 = (byte)0;
                byte OutOfN1 = (byte)0;
                EncodeInt32NumberTo4B((int)1, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

                //Header part
                byte[] send = new byte[416];
                send[0] = (byte)(Action.DATA);
                for (int i = 1; i <= 7; i++)
                    send[i] = (byte)(NA.NA);
                send[8] = (byte)(Nth16M);
                send[9] = (byte)(Nth65k);
                send[10] = (byte)(Nth256);
                send[11] = (byte)(Nth1);
                send[12] = (byte)(OutOfN16M);
                send[13] = (byte)(OutOfN65k);
                send[14] = (byte)(OutOfN256);
                send[15] = (byte)(OutOfN1);

                //Payload part
                Array.Copy(payload, 0, send, 16, payload.Length);

                //Encrypt send, encrypted_send
                byte[] encrypted_send = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);

                //Add to 1->datagram dictionary since we have only 1 datagram tbs
                datagrams.Add(1, encrypted_send);
            }
            //Case of more than one content datagrams to be sent
            else
            {
                //Payload >400+ cut to datagrams like |DATA 400|rest... until it's possible
                int ith = 1;
                int outOfN = (payload.Length / 400) + 1;
                while (payload.Length > 400)
                {
                    //Number precalculation ith/outOfN

                    //Set Nth16M, Nth65k, Nth256, Nth1
                    byte ith16M = (byte)0;
                    byte ith65k = (byte)0;
                    byte ith256 = (byte)0;
                    byte ith1 = (byte)0;
                    EncodeInt32NumberTo4B(ith, ref ith16M, ref ith65k, ref ith256, ref ith1);

                    //Set OutOfN16M, OutOfN65k, OutOfN256, OutOfN1
                    byte OutOfN16M = (byte)0;
                    byte OutOfN65k = (byte)0;
                    byte OutOfN256 = (byte)0;
                    byte OutOfN1 = (byte)0;
                    EncodeInt32NumberTo4B(outOfN, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

                    //DATA datagram prepataion
                    byte[] send = new byte[416];
                    //Header part
                    send[0] = (byte)(Action.DATA);
                    for (int i = 1; i <= 7; i++)
                    {
                        send[i] = (byte)(NA.NA);
                    }
                    send[8] = (byte)(ith16M);
                    send[9] = (byte)(ith65k);
                    send[10] = (byte)(ith256);
                    send[11] = (byte)(ith1);
                    send[12] = (byte)(OutOfN16M);
                    send[13] = (byte)(OutOfN65k);
                    send[14] = (byte)(OutOfN256);
                    send[15] = (byte)(OutOfN1);

                    //Payload part 
                    //Copy first 400 bytes from whole payload
                    Array.Copy(payload, 0, send, 16, 400);

                    //Cut these first 400 bytes from the whole payload out
                    byte[] truncatedPayload = payload.Skip(400).ToArray();
                    payload = truncatedPayload;

                    //Encrypt send, encrypted_send
                    byte[] encrypted_send = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);

                    //Add to dictionary as current i->datagram
                    datagrams.Add(ith, encrypted_send);

                    //Incr i for following datagram in the row
                    ith++;
                }

                //Remainder number of bytes from % 400
                if (payload.Length != 0)
                {
                    //Number precalculation ith/outOfN

                    //Set Nth16M, Nth65k, Nth256, Nth1
                    byte ith16M = (byte)0;
                    byte ith65k = (byte)0;
                    byte ith256 = (byte)0;
                    byte ith1 = (byte)0;
                    EncodeInt32NumberTo4B(ith, ref ith16M, ref ith65k, ref ith256, ref ith1);

                    //Set OutOfN16M, OutOfN65k, OutOfN256, OutOfN1
                    byte OutOfN16M = (byte)0;
                    byte OutOfN65k = (byte)0;
                    byte OutOfN256 = (byte)0;
                    byte OutOfN1 = (byte)0;
                    EncodeInt32NumberTo4B(outOfN, ref OutOfN16M, ref OutOfN65k, ref OutOfN256, ref OutOfN1);

                    //Last DATA datagram
                    byte[] send = new byte[416];
                    //Header part
                    send[0] = (byte)(Action.DATA);
                    for (int i = 1; i <= 7; i++)
                    {
                        send[i] = (byte)(NA.NA);
                    }
                    send[8] = (byte)(ith16M);
                    send[9] = (byte)(ith65k);
                    send[10] = (byte)(ith256);
                    send[11] = (byte)(ith1);
                    send[12] = (byte)(OutOfN16M);
                    send[13] = (byte)(OutOfN65k);
                    send[14] = (byte)(OutOfN256);
                    send[15] = (byte)(OutOfN1);
                    //Payload part 
                    Array.Copy(payload, 0, send, 16, payload.Length);

                    //Encrypt send, encrypted_send
                    byte[] encrypted_send = Encryption.Encrypt(send, this.EncryptionKeyDefault, this.EncryptionIVDefault);

                    //Add to dictionary as last i->datagram
                    datagrams.Add(ith, encrypted_send);

                    //Not necessary at this point
                    //ith++;
                }
                //Payload serialized and prepared to be sent
            }

            //Send all parts of encrypted payload to server
            foreach (KeyValuePair<int, byte[]> d in datagrams)
            {
                s.Send(d.Value, 416);
            }

            //Wait max two seconds for ACK or RESEND to terminate or proceed
            byte[] reReq = new byte[416];
            var asyncReReq = s.BeginReceive(null, null);
            asyncReReq.AsyncWaitHandle.WaitOne(TwoSeconds);
            if (asyncReReq.IsCompleted)
            {
                try
                {
                    IPEndPoint remoteEP = null;
                    reReq = s.EndReceive(asyncReReq, ref remoteEP);
                    byte[] reReqDecrypted = Encryption.Decrypt(reReq, this.EncryptionKeyDefault, this.EncryptionIVDefault);
                    if (reReqDecrypted[0] == (byte)(Action.ACK))
                    {
                        //Nice Success
                        return true;
                    }
                    else if (reReqDecrypted[0] == (byte)(Action.ERR))
                    {
                        throw new ServerInsertFailure();
                        //return false;
                    }
                    else if (reReqDecrypted[0] == (byte)(Action.RESEND))
                    {
                        //Count Up To 2x missing
                        int attempt = 1;
                        int attempts = 2 * load;

                        //Change Reference To next
                        byte[] next = reReqDecrypted;

                        //While we're not getting ACK 

                        //WHAT IF 7/12 STOP RECEIVING EVERYTHING
                        while ((next[0] != (byte)(Action.ACK)) || (attempt < attempts))
                        {
                            //We've Not Exceeded Number Of Attempts
                            if (attempt < attempts)
                            {
                                if (next[0] == (byte)(Action.RESEND))
                                {
                                    //Resend
                                    int r = PayloadResendID(next);
                                    s.Send(datagrams[r], 416);
                                    //Recv Next
                                    next = null;
                                    next = new byte[416];

                                    var recvNext = s.BeginReceive(null, null);
                                    recvNext.AsyncWaitHandle.WaitOne(HalfOfSecond);
                                    //Received
                                    if (recvNext.IsCompleted)
                                    {
                                        try
                                        {
                                            IPEndPoint remoteEPNext = null;
                                            //next = s.EndReceive(recvNext, ref remoteEPNext); //we're not decrypting
                                            next = Encryption.Decrypt(s.EndReceive(recvNext, ref remoteEPNext), this.EncryptionKeyDefault, this.EncryptionIVDefault); //we are decrypting
                                            //Just Receive And Let The Loop Proceed To Evaluation In Header
                                            //↓
                                        }
                                        catch (Exception ex)
                                        {
                                            throw new SocketRuntimeError();
                                            //return false;
                                        }
                                    }
                                    //Not Received And Not Complete Payload Still Fragmented
                                    else
                                    {
                                        throw new DeliveryFragmentation();
                                    }
                                    //Increment Attempt Counter
                                    attempt++;
                                }
                                else
                                {
                                    throw new ProtocolFlowError();
                                    //return false;
                                }
                            }
                            //We Have Exceeded Number Of Attempts
                            else
                            {
                                break;
                            }
                        }
                        if (attempt == attempts)
                        {
                            throw new DeliveryFragmentation();
                            //return false;
                        }
                        else
                        {
                            //SUCC
                            return true;
                        }
                    }
                    else
                    {
                        throw new ProtocolFlowError();
                        //return false;
                    }
                }
                catch (Exception e)
                {
                    throw new SocketRuntimeError();
                    //return false;
                }
            }
            else
            {
                throw new Timeouted();
                //return false;
            }
            //RESEND slash ACK Ending
            //if failed
            //return false;
        }
    //END of function
    }
}

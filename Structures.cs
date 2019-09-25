using System;
using Newtonsoft.Json.Converters;
using System.Collections.Generic;
using System.Text;
using Newtonsoft.Json;

namespace XamarinLTest
{
    public class AuthPack
    {
        //UserRights authpack.rights in the CMS
        public enum UserRights { Rozhodci=0, VedouciKlubu=1, SuperUser=2  }
        //Test exptime of authorization on the phone
        public enum Expire : ulong { After=1680000 }

        public Int32 id;
        public String first_name;
        public String last_name;
        public Int32 affiliation;
        public String affil_text;
        public Int32 rights;
        public String auth_tkn;
        public ulong timestamp;
        //public ulong _aux_created;

        //Deserializize AuthPack Constructor for Newtonsoft.JSON; List<User>
        [JsonConstructor]
        public AuthPack(Int32 id, String first_name, String last_name, Int32 affiliation, String affil_text, Int32 rights, String auth_tkn, ulong timestamp)
        {
            this.id = id;
            this.first_name = first_name;
            this.last_name = last_name;
            this.affiliation = affiliation;
            this.affil_text = affil_text;
            this.rights = rights;
            this.auth_tkn = auth_tkn;
            this.timestamp = timestamp;
            //this._aux_created = (ulong)DateTimeOffset.Now.ToUnixTimeMilliseconds();
        }

        //Is this authentication still valid?
        public bool isStillValid()
        {
            ulong now = (ulong)DateTimeOffset.Now.ToUnixTimeMilliseconds();

            if ((now - timestamp) < (ulong)Expire.After)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public String SerializeAuthTkn()
        {
            String _auth_tkn = "{\"auth_tkn\":\""+this.auth_tkn+"\"}";
            return _auth_tkn;
        }

    }
    public class Post
    {
        public String id { get; set; }
        public String timestamp { get; set; }
        public String title { get; set; }
        public String content { get; set; }
        /*public Post()
        {

        }*/
        [JsonConstructor]
        private Post(String id, String timestamp, String title, String content)
        {
            this.id = id;
            this.timestamp = timestamp;
            this.title = title;
            this.content = content;
        }

        //in json {"id":"8","timestamp":"2018-03-24 00:57:17","title":"Ahojj","content":"Hello,j World!!"}, out instance of Post
        public static bool TryParse(String json, out Post a)
        {
            //String testJson = "{\"id\":\"8\",\"timest24 00:57:17\",\"title\":\"Ahojj\",\"content\":\"<p>Hello,j World!!</p>\"}";
            var converter = new ExpandoObjectConverter();
            Post parsedPost;
            try
            {
                parsedPost = JsonConvert.DeserializeObject<Post>(json, converter);
                a = parsedPost;
                return true;
            }
            catch (Exception e)
            {
                a = null;
                return false;
            }
        }

        //"{\"id\":\"{0}\",\"timestamp\":\"{1}\",\"title\":\"{2}\",\"content\":\"{3}\"}"
        //{"id":"0","timestamp":"2018-07-16 15:42","title":"Titulek","content":"Lorem ipsum..."}
        public String Serialize()
        {
            object[] args = new object[] { this.id, this.timestamp, this.title, this.content };
            String json = String.Format("\"id\":\"{0}\",\"timestamp\":\"{1}\",\"title\":\"{2}\",\"content\":\"{3}\"", args);
            json = "{" + json + "}";
            //String json = String.Format("{\"id\":\"{0}\",\"timestamp\":\"{1}\",\"title\":\"{2}\",\"content\":\"{3}\"}", args);
            return json;
        }

        //{"id":"12","title":"Aktualita o plavani","content":"Nove se stalo blablabla..."}
        /*
        public String SerializeIdTitleContent()
        {
            object[] args = new object[] { this.id, this.title, this.content };
            String json = String.Format("\"id\":\"{0}\",\"title\":\"{1}\",\"content\":\"{2}\"", args);
            json = "{" + json + "}";
            return json;
        }
        */

        //{"title":"Novinky o plavani","content":"Od noveho roku se meni..."}
        public String SerializeUpdate()
        {
            object[] args = new object[] { this.title, this.content };
            String json = String.Format("\"title\":\"{0}\",\"content\":\"{1}\"", args);
            json = "{" + json + "}";
            return json;
        }
    }
    public class Cup
    {
        enum CupVolume { Full, Slim }

        Int32 Volume { get; set; }
        public Int32 id { get; set; }
        public String date { get; set; }
        public String name { get; set; }
        String description { get; set; }
        Int32? owningclub { get; set; }

        //Deserializize Cup Constructor for Newtonsoft.JSON; List<Cup>(JSON)
        [JsonConstructor]
        public Cup(Int32 id, String date, String name)
        {
            this.Volume = (Int32)CupVolume.Slim;
            this.id = id;
            this.date = date;
            this.name = name;
            this.description = null;
            this.owningclub = null;
        }
        private Cup(Int32 id, String date, String name, String description, Int32 owningclub)
        {
            this.Volume = (Int32)CupVolume.Full;
            this.id = id;
            this.date = date;
            this.name = name;
            this.description = description;
            this.owningclub = owningclub;
        }
        //TODO
        static bool TryParse(String _json, out Cup e)
        {
            e = null;
            return false;
        }
    }
    public class User
    {
        enum UserVolume { Full, Slim }

        public Int32 Volume { get; set; }
        public Int32 id { get; set; }
        public String first_name { get; set; }
        public String last_name { get; set; }
        public String email { get; set; }
        //Boolean? Active { get; set; }
        public Boolean? approved { get; set; }
        public Int32? rights { get; set; }
        public Int32 affiliation { get; set; }

        public User() { }
        //Deserializize User Constructor for Newtonsoft.JSON; List<User>
        [JsonConstructor]
        public User (Int32 id, String first_name, String last_name, Int32 affiliation)
        {
            this.Volume = (Int32)UserVolume.Slim;
            this.id = id;
            this.first_name = first_name;
            this.last_name = last_name;
            this.email = null;
            this.approved = null;
            this.rights = null;
            this.affiliation = affiliation;
        }
        /*5. arg Boolean _active,*/
        private User(Int32 id, String first_name, String last_name, String email,
                    Boolean approved, Int32 rights, Int32 affiliation)
        {
            this.Volume = (Int32)UserVolume.Full;
            this.id = id;
            this.first_name = first_name;
            this.last_name = last_name;
            this.email = email;
            //this.Active = _active;
            this.approved = approved;
            this.rights = rights;
            this.affiliation = affiliation;
        }
        
        //TODO
        static bool TryParse(String _json, out User u)
        { 
            //split.Count is 8...Full, split is 3...Slim, 
            //tryparse full user User((Int32)UserVolume.Full, whatever_id ... , null, null);
            //tryparse full user User((Int32)UserVolume.Slim, whatever_id, ... , null, null);
            //return user;
            //truparse nahled user
            u = null;
            return false;
        }
    }
    public class Club
    {
        enum UserVolume { Full, Slim }

        Int32 Volume { get; set; }
        public Int32 id { get; set; }
        public String name { get; set; }
        public String zkratka { get; set; }
        public Int32? idklubu { get; set; }
        public String img { get; set; }

        [JsonConstructor]
        public Club(Int32 id, String name)
        {
            this.Volume = (Int32)UserVolume.Slim;
            this.id = id;
            this.name = name;
            zkratka = null;
            idklubu = null;
            img = null;
        }
        private Club(Int32 id, String name, String zkratka, Int32 idklubu, String img)
        {
            this.Volume = (Int32)UserVolume.Full;
            this.id = id;
            this.name = name;
            this.zkratka = zkratka;
            this.idklubu = idklubu;
            this.img = img;
        }

    }
    public class Reply
    {
        public String answer { get; set; }

        //{"answer":"true"} or {"answer":"false"} TRUTH VALUE AS STRING
        [JsonConstructor] //Specify Newtonsoft constructor
        public Reply(String answer)
        {
            this.answer = answer;
        }
        public bool IsTrue()
        {
            if (answer == "true")
            {
                return true;
            }
            else if (answer == "false")
            {
                return false;
            }
            throw new ReplyAnswerException();
        }
    }

    //DEPRECATE FOR SAKE OF User SLIM?
    //User ID - Cup ID
    public class __depr_PairUserIDCupID
    {
        int _userID;
        int _cupID;
    }

    //DEPRECATE? FOR SAKE OF User SLIM?
    // {"1" "Stepan Klos"}
    public class __depr_Nametag
    {
        int userID;
        String nametag;
    }

    // {"id":"1","position":"Hlavni rozhodci"}
    public class Position
    {
        public int id;
        public String position;
        //Public constructor
        [JsonConstructor]
        public Position(int id, String position)
        {
            this.id = id;
            this.position = position;
        }
    }

    // {"idpoz":"1","iduser":"1"}
    public class PairIDPozIDUser
    {
        public int idpoz;
        public int iduser;
        //Public constructor
        [JsonConstructor]
        public PairIDPozIDUser(int idpoz, int iduser)
        {
            this.idpoz = idpoz;
            this.iduser = iduser;
        }
        public string Serialize()
        {
            string _ser="{\"idpoz\":\""+idpoz+"\",\"iduser\":\""+iduser+"\"}";
            return _ser;
        }
    }

    // {"idcup":"6","iduser":"1"}
    public class PairIDCupIDUser
    {
        public int idcup;
        public int iduser;
        //Public constructor
        [JsonConstructor]
        public PairIDCupIDUser(int idcup, int iduser)
        {
            this.idcup = idcup;
            this.iduser = iduser;
        }
        public string Serialize()
        {
            string _ser= "{\"idcup\":\"" + idcup + "\",\"iduser\":\"" + iduser + "\"}";
            return _ser;
        }
    }
}

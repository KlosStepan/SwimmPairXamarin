using System;
using System.Collections.Generic;
using System.Text;
using System.Security.Cryptography;
using System.IO;

namespace XamarinLTest
{
    //https://stackoverflow.com/questions/11873878/c-sharp-encryption-to-php-decryption
    public static class Encryption
    {   //            byte[]              string prm_text_to_encrypt
        public static byte[] Encrypt(byte[] payload, string prm_key, string prm_iv)
        {
            //var sToEncrypt = prm_text_to_encrypt;

            var rj = new RijndaelManaged()
            {
                Padding = PaddingMode.Zeros,
                Mode = CipherMode.CBC,
                KeySize = 256,
                BlockSize = 256,
            };

            var key = Convert.FromBase64String(prm_key);
            var IV = Convert.FromBase64String(prm_iv);

            var encryptor = rj.CreateEncryptor(key, IV);

            var msEncrypt = new MemoryStream();
            var csEncrypt = new CryptoStream(msEncrypt, encryptor, CryptoStreamMode.Write);

            //var toEncrypt = Encoding.ASCII.GetBytes(sToEncrypt);
            var toEncrypt = payload;

            csEncrypt.Write(toEncrypt, 0, toEncrypt.Length);
            csEncrypt.FlushFinalBlock();

            //var encrypted = msEncrypt.ToArray();
            var encrypted = msEncrypt.ToArray();
            //return (Convert.ToBase64String(encrypted));
            return (encrypted);
        }
        //            string        string prm_text_to_decrypt
        public static byte[] Decrypt(byte[] encryptedPayload, string prm_key, string prm_iv)
        {

            //var sEncryptedString = prm_text_to_decrypt;

            var rj = new RijndaelManaged()
            {
                Padding = PaddingMode.Zeros,
                Mode = CipherMode.CBC,
                KeySize = 256,
                BlockSize = 256,
            };

            var key = Convert.FromBase64String(prm_key);
            var IV = Convert.FromBase64String(prm_iv);

            var decryptor = rj.CreateDecryptor(key, IV);

            //var sEncrypted = Convert.FromBase64String(sEncryptedString);
            var sEncrypted = encryptedPayload;
            var fromEncrypt = new byte[sEncrypted.Length];

            var msDecrypt = new MemoryStream(sEncrypted);
            var csDecrypt = new CryptoStream(msDecrypt, decryptor, CryptoStreamMode.Read);

            csDecrypt.Read(fromEncrypt, 0, fromEncrypt.Length);
            //PKCS7 remove obfuscation last 32 bytes
            //Array.Resize(ref fromEncrypt, fromEncrypt.Length - 32);
            return (fromEncrypt);
        }
        //            generate keys to be hardcoded, copied out thru inspector. Don't delete this despite 0 references!
        public static void GenerateKeyIV(out string key, out string IV, out string hexKey, out string hexIV)
        {
            var rj = new RijndaelManaged()
            {
                Padding = PaddingMode.Zeros,
                Mode = CipherMode.CBC,
                KeySize = 256,
                BlockSize = 256,
            };
            rj.GenerateKey();
            rj.GenerateIV();

            key = Convert.ToBase64String(rj.Key);
            IV = Convert.ToBase64String(rj.IV);
            //hex kokoti here
            StringBuilder sbKey = new StringBuilder();
            foreach (byte b in rj.Key)
                sbKey.Append(b.ToString("X2"));

            string HexKey = sbKey.ToString();
            hexKey = HexKey;

            StringBuilder sbIV = new StringBuilder();
            foreach (byte b in rj.IV)
                sbIV.Append(b.ToString("X2"));

            string HexIV = sbIV.ToString();
            hexIV = HexIV;
            int i = 5;
        }
    }
}

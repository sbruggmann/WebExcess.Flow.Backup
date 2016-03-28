<?php
namespace WebExcess\Flow\Backup\Service;

/*
 * This file is part of the WebExcess.Flow.Backup package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

class CryptService
{

    /**
     * @var \Crypto
     */
    protected $crypto;

    /**
     * @var string
     */
    protected $key;

    /**
     * @Flow\Inject
     * @var Files
     */
    protected $files;

    /**
     * CryptService constructor.
     *
     * @param string $key
     * @return void
     */
    public function initialize($key)
    {
        $this->crypto = new \Crypto;
        $this->key = $key;
    }

    /**
     * @param string $content
     * @return string
     * @throws \CannotPerformOperationException
     */
    public function encrypt($content)
    {
        try {
            return $this->crypto->Encrypt($content, $this->key);
        } catch (Ex\CryptoTestFailedException $ex) {
            die('Cannot safely perform encryption');
        } catch (Ex\CannotPerformOperationException $ex) {
            die('Cannot safely perform encryption');
        }
    }

    /**
     * @param string $fileFrom Path and Filename
     * @param string $fileTo Path and Filename
     * @return int|false
     */
    public function encryptFileToFile($fileFrom, $fileTo)
    {
        $this->createDirectoryRecursivelyByFilename($fileTo);
        $encryptedContent = $this->encrypt(file_get_contents($fileFrom));

        return file_put_contents($fileTo, $encryptedContent);
    }

    /**
     * @param string $encryptedContent
     * @return string
     * @throws \CannotPerformOperationException
     */
    public function decrypt($encryptedContent)
    {
        try {
            return $this->crypto->Decrypt($encryptedContent, $this->key);
        } catch (Ex\InvalidCiphertextException $ex) { // VERY IMPORTANT
            // Either:
            //   1. The ciphertext was modified by the attacker,
            //   2. The key is wrong, or
            //   3. $ciphertext is not a valid ciphertext or was corrupted.
            // Assume the worst.
            die('DANGER! DANGER! The ciphertext has been tampered with!');
        } catch (Ex\CryptoTestFailedException $ex) {
            die('Cannot safely perform decryption');
        } catch (Ex\CannotPerformOperationException $ex) {
            die('Cannot safely perform decryption');
        }
    }

    /**
     * @param string $fileFrom Path and Filename
     * @param string $fileTo Path and Filename
     * @return int|false
     */
    public function decryptFileToFile($fileFrom, $fileTo)
    {
        $this->createDirectoryRecursivelyByFilename($fileTo);
        $content = $this->decrypt(file_get_contents($fileFrom));

        return file_put_contents($fileTo, $content);
    }

    /**
     * @param string $filename
     * @throws \TYPO3\Flow\Utility\Exception
     * @return void
     */
    public function createDirectoryRecursivelyByFilename($filename)
    {
        $this->files->createDirectoryRecursively(dirname($filename));
    }

}

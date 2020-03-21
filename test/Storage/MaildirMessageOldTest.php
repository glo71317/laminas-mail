<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mail\Storage;

use Laminas\Mail\Storage;
use PHPUnit\Framework\TestCase;

class MaildirMessageOldTest extends TestCase
{
    protected $originalMaildir;
    protected $maildir;
    protected $tmpdir;

    public function setUp()
    {
        $this->originalMaildir = __DIR__ . '/../_files/test.maildir/';
        if (! getenv('TESTS_LAMINAS_MAIL_MAILDIR_ENABLED')) {
            $this->markTestSkipped('You have to unpack maildir.tar in Laminas/Mail/_files/test.maildir/ '
                                 . 'directory before enabling the maildir tests');
            return;
        }

        if ($this->tmpdir == null) {
            if (getenv('TESTS_LAMINAS_MAIL_TEMPDIR') != null) {
                $this->tmpdir = getenv('TESTS_LAMINAS_MAIL_TEMPDIR');
            } else {
                $this->tmpdir = __DIR__ . '/../_files/test.tmp/';
            }
            if (! file_exists($this->tmpdir)) {
                mkdir($this->tmpdir);
            }
            $count = 0;
            $dh = opendir($this->tmpdir);
            while (readdir($dh) !== false) {
                ++$count;
            }
            closedir($dh);
            if ($count != 2) {
                $this->markTestSkipped('Are you sure your tmp dir is a valid empty dir?');
                return;
            }
        }

        $this->maildir = $this->tmpdir;

        foreach (['cur', 'new'] as $dir) {
            mkdir($this->tmpdir . $dir);
            $dh = opendir($this->originalMaildir . $dir);
            while (($entry = readdir($dh)) !== false) {
                $entry = $dir . '/' . $entry;
                if (! is_file($this->originalMaildir . $entry)) {
                    continue;
                }
                copy($this->originalMaildir . $entry, $this->tmpdir . $entry);
            }
            closedir($dh);
        }
    }

    public function tearDown()
    {
        foreach (['cur', 'new'] as $dir) {
            if (! is_dir($this->tmpdir . $dir)) {
                continue;
            }
            $dh = opendir($this->tmpdir . $dir);
            while (($entry = readdir($dh)) !== false) {
                $entry = $this->tmpdir . $dir . '/' . $entry;
                if (! is_file($entry)) {
                    continue;
                }
                unlink($entry);
            }
            closedir($dh);
            rmdir($this->tmpdir . $dir);
        }
    }


    public function testFetchHeader()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);

        $subject = $mail->getMessage(1)->subject;
        $this->assertEquals('Simple Message', $subject);
    }

/*
    public function testFetchTopBody()
    {
        $mail = new TestAsset\MaildirOldMessage(array('dirname' => $this->maildir));

        $content = $mail->getHeader(3, 1)->getContent();
        $this->assertEquals('Fair river! in thy bright, clear flow', trim($content));
    }
*/
    public function testFetchMessageHeader()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);

        $subject = $mail->getMessage(1)->subject;
        $this->assertEquals('Simple Message', $subject);
    }

    public function testFetchMessageBody()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);

        $content = $mail->getMessage(3)->getContent();
        list($content) = explode("\n", $content, 2);
        $this->assertEquals('Fair river! in thy bright, clear flow', trim($content));
    }

    public function testHasFlag()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);

        $this->assertFalse($mail->getMessage(5)->hasFlag(Storage::FLAG_SEEN));
        $this->assertTrue($mail->getMessage(5)->hasFlag(Storage::FLAG_RECENT));
        $this->assertTrue($mail->getMessage(2)->hasFlag(Storage::FLAG_FLAGGED));
        $this->assertFalse($mail->getMessage(2)->hasFlag(Storage::FLAG_ANSWERED));
    }

    public function testGetFlags()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);

        $flags = $mail->getMessage(1)->getFlags();
        $this->assertTrue(isset($flags[Storage::FLAG_SEEN]));
        $this->assertContains(Storage::FLAG_SEEN, $flags);
    }

    public function testFetchPart()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);
        $this->assertEquals($mail->getMessage(4)->getPart(2)->contentType, 'text/x-vertical');
    }

    public function testPartSize()
    {
        $mail = new TestAsset\MaildirOldMessage(['dirname' => $this->maildir]);
        $this->assertEquals($mail->getMessage(4)->getPart(2)->getSize(), 80);
    }
}

<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{
    /**
     * @return void
     */
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }

    /**
     * @return void
     */
    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertCount(2, $crawler->filter('h4'));
        $client->clickLink('View');
        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
    }

    /**
     * @return void
     */
    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $email = 'me@automat.ed';
        $client->submitForm('Submit', [
            'comment_type_form[author]' => 'Fabien',
            'comment_type_form[text]'  => 'Some feedback from an automated functional test',
            'comment_type_form[email]' => $email,
            'comment_type_form[photo]' => dirname(__DIR__, 2) .'/public/images/under_construction.gif'
            ]);
        $comment = self::$container->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::$container->get(EntityManagerInterface::class)->flush();
        $this->assertSelectorExists('p:contains("Some feedback from an automated functional test")');
    }
}
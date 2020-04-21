<?php

namespace App\Tests;

use App\Entity\Comment;
use App\Spam\Checker;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;


class SpamCheckerTest extends TestCase
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testSpamScopeWithInvalidRequest()
    {
        $comment = new Comment();
        $context = [];
        $client = new MockHttpClient([new MockResponse('invalid', [
            'response_headers' => ['x-akismet-debug-help: Invalid key']
        ])]);
        $checker = new Checker($client, 'just_key');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid(Invalid key).');
        $checker->getSpamScope($comment, $context);
    }

    /**
     * @dataProvider getComments
     * @param int $expectedScope
     * @param ResponseInterface $response
     * @param Comment $comment
     * @param array $context
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testSpamScope(int $expectedScope, ResponseInterface $response, Comment $comment, array $context)
    {
        $client = new MockHttpClient([$response]);
        $checker = new Checker($client, 'just_key');
        $score = $checker->getSpamScope($comment, $context);
        $this->assertSame($expectedScope, $score);
    }

    /**
     * @return iterable
     */
    public function getComments(): iterable
    {
        $comment = new Comment();
        $context = [];
        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [2, $response, $comment, $context];
        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];
        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }
}

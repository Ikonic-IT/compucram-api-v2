<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 2/18/17
 * Time: 11:19 PM
 */

namespace Hondros\Unit\Api\Client;

use Hondros\Api\Client\MailChimp;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use DateTime;
use stdClass;

class MailChimpTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * make sure the correct params are created that get sent over to mailchimp
     */
    public function testAddSubscribeToListMergeFields()
    {
        $date = new DateTime();
        $user = $this->getSampleUser();
        $listId = 'testlist';
        $listUrl = 'lists/testlist/members';
        $productId = 21;

        $subscriber = (new MailChimp\Subscriber())
            ->setEmail($user->getEmail())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setEnrollmentDate($date)
            ->setProductId($productId);

        $params = [
            'email_address' => $subscriber->getEmail(),
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $subscriber->getFirstName(),
                'LNAME' => $subscriber->getLastName(),
                'ENROLLDATE' => $subscriber->getEnrollmentDate(),
                'PRODUCTID' => $subscriber->getProductId()
            ]
        ];

        $mockResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $object = new stdClass();
        $object->id = 1001;
        $mockResponse->shouldReceive('getBody')->andReturn(json_encode($object));

        $mockGuzzle = $this->getMockGuzzleClient();
        $mockGuzzle->shouldReceive('post')->withArgs([$listUrl, ['json' => $params]])->andReturn($mockResponse);

        $client = new MailChimp($mockGuzzle, $this->getMockLogger());
        $response = $client->addSubscriberToList($listId, $subscriber);

        $this->assertInstanceOf(MailChimp\Subscriber::class, $response);
        $this->assertEquals($object->id, $response->getId());
    }

    /**
     * @return \Mockery\MockInterface
     */
    protected function getMockGuzzleClient()
    {
        return m::mock('GuzzleHttp\Client');
    }

    /**
     * @return \Mockery\MockInterface
     */
    protected function getMockLogger()
    {
        return m::mock('Monolog\Logger');
    }

    /** move this to a user util */
    protected function getSampleUser()
    {
        return (new \Hondros\Api\Model\Entity\User())
            ->setEmail('panda@powa.co')
            ->setFirstName('Panda')
            ->setLastName('Powa');
    }
}

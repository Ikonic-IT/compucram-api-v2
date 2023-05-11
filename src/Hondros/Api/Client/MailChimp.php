<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 8/13/15
 * Time: 1:59 PM
 */

namespace Hondros\Api\Client;

use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class MailChimp
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    public function __construct(\GuzzleHttp\Client $client, Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Check if the contact is already in a list
     *
     * @param string $listId
     * @param string $email
     * @return bool
     */
    public function isSubscriberInList($listId, $email)
    {
        $memberHash = md5(strtolower($email));
        try {
            $response = $this->client->get("lists/{$listId}/members/{$memberHash}");
        } catch (TransferException $e) {
            $message = $e->getMessage();

            if ($e->hasResponse()) {
                $message .= ' ' . $e->getResponse()->getReasonPhrase();
            }

            // log it
            $this->logger->error(__METHOD__ . $message);

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        return true;
    }

    /**
     * Adds the subscriber to a list
     *
     * @param int $listId
     * @param MailChimp\Subscriber $subscriber
     * @return bool|MailChimp\Subscriber
     */
    public function addSubscriberToList($listId, MailChimp\Subscriber $subscriber)
    {
        $params = [
            'email_address' => $subscriber->getEmail(),
            'status' => 'subscribed',
            'merge_fields' => $subscriber->getMergeFields()
        ];

        try {
            $response = $this->client->post("lists/{$listId}/members", [
                'json' => $params
            ]);
        } catch (TransferException $e) {
            $message = $e->getMessage();

            if ($e->hasResponse()) {
                $message .= ' ' . $e->getResponse()->getReasonPhrase();
            }

            // log it
            $this->logger->error(__METHOD__ . $message, $params);

            return false;
        }

        $data = (string) $response->getBody();

        if (empty($data)) {
            return true;
        }

        $json = json_decode($data);
        $subscriber->setId($json->id);

        return $subscriber;
    }

    /**
     * @param int $listId
     * @param string $email
     * @return bool
     */
    public function removeFromList($listId, $email)
    {
        try {
            $response = $this->client->delete("lists/{$listId}/members/" . md5(strtolower($email)));
        } catch (TransferException $e) {
            $message = $e->getMessage();

            if ($e->hasResponse()) {
                $message .= ' ' . $e->getResponse()->getReasonPhrase();
            }

            // log it
            $this->logger->error(__METHOD__ . $message, [
                'listId' => $listId,
                'email' => $email
            ]);

            return false;
        }

        return true;
    }
}
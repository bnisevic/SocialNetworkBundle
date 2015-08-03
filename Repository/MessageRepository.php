<?php
/*
 * This file is part of the SocialNetworkBundle package.
 *
 * (c) Fulgurio <http://fulgurio.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fulgurio\SocialNetworkBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fulgurio\SocialNetworkBundle\Entity\Message;


/**
 * MessageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MessageRepository extends EntityRepository
{
    /**
     * Find user root messages
     *
     * @param User $user
     */
    public function findRootMessages($user)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT m, mt.has_read
                FROM FulgurioSocialNetworkBundle:Message m
                JOIN m.target mt
                WHERE mt.target=:user
                    AND m.parent IS NULL
                ORDER BY m.updated_at DESC');
        $query->setParameter('user', $user);
        return $query->getResult();
    }

    /**
     * Find message participants
     *
     * @param Message $message
     */
    public function findParticipants(Message $message)
    {
        $query = $this->getEntityManager()->createQuery(
                'SELECT u
                FROM FulgurioSocialNetworkBundle:User u
                JOIN u.msgTarget m
                WHERE m.message = :message
                    AND u.enabled = 1
                ORDER BY u.username');
        $query->setParameter('message', $message);
        return $query->getResult();
    }

    /**
     * Update root message has read flag for specified users
     *
     * @param Message $message
     * @param Collection $users
     */
    public function markRootAsUnread(Message $message, $users)
    {
        $query = $this->getEntityManager()->createQuery(
                'UPDATE FulgurioSocialNetworkBundle:MessageTarget mt
                SET mt.has_read=0
                WHERE mt.target IN (:users)
                    AND mt.message = :message');
        $query->setParameter('message', $message);
        $query->setParameter('users', $users);
        $query->getSingleScalarResult();
    }

    /**
     * Get number of unread message
     *
     * @param Message $message
     * @param Collection $users
     */
    public function countUnreadMessage($user)
    {
        $query = $this->getEntityManager()->createQuery('SELECT COUNT (mt) FROM FulgurioSocialNetworkBundle:MessageTarget mt WHERE mt.has_read = 0 AND mt.target = :user');
        $query->setParameter('user', $user);
        return $query->getSingleScalarResult();
    }

    /**
     * Remove relation between user and message (and message children too)
     *
     * @param Message | integer $msg
     * @param User | integer $user
     */
    public function removeUserMessageRelation($msg, $user)
    {
        $em = $this->getEntityManager();
        $query1 = $em->createQuery(
                'SELECT m
                FROM FulgurioSocialNetworkBundle:Message m
                WHERE m.id=:msg
                    OR m.parent = :msg');
        $query1->setParameter('msg', $msg);
        $msgList = $query1->getResult();

        $query2 = $em->createQuery(
                'DELETE FROM FulgurioSocialNetworkBundle:MessageTarget mt
                WHERE mt.target = :user
                    AND mt.message IN (:msgs)');
        $query2->setParameter('user', $user);
        $query2->setParameter('msgs', $msgList);
        $query2->getSingleScalarResult();
    }
}
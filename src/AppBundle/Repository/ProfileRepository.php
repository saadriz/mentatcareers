<?php
/**
 * Created by PhpStorm.
 * User: Marwen
 * Date: 03/04/2016
 * Time: 17:18
 */

namespace AppBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ProfileRepository extends EntityRepository
{

    public function retrieveProfilesWithNotEmptyEmail() {
        $result = $this->_em->createQueryBuilder()
            ->select('profile')
            ->from('AppBundle:Profile', 'profile')
            ->where("profile.email != 'a:0:{}'")->getQuery()->getResult();
        return $result;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Marwen
 * Date: 03/04/2016
 * Time: 10:34
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Profile
 *
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(name="search_idx", columns={"name"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProfileRepository")
 */
class Profile
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var array
     * @ORM\Column(name="email", type="array")
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(name="resume_scrapped", type="text")
     */
    private $resumeScrapped;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getResumeScrapped()
    {
        return $this->resumeScrapped;
    }

    /**
     * @param $resumeScrapped
     * @return $this
     */
    public function setResumeScrapped($resumeScrapped)
    {
        $this->resumeScrapped = $resumeScrapped;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

}
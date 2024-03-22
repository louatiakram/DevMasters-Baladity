<?php

namespace App\Entity;

use App\Repository\PubliciteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PubliciteRepository::class)]
class Publicite
{
   
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id_pub;

    #[ORM\Column(type: 'string', length: 255)]
    private $titre_pub;

    #[ORM\Column(type: 'string', length: 255)]
    private $description_pub;

    #[ORM\Column(type: 'integer')]
    private $contact_pub;

    #[ORM\Column(type: 'string', length: 255)]
    private $localisation_pub;

    #[ORM\Column(type: 'integer')]
    private $id_a;

    #[ORM\ManyToOne(targetEntity: enduser::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    private $id_user;

    #[ORM\Column(type: 'string', length: 255)]
    private $image_pub;

    #[ORM\Column(type: 'string', length: 255)]
    private $offre_pub;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPub(): ?int
    {
        return $this->id_pub;
    }

    public function setIdPub(int $id_pub): self
    {
        $this->id_pub = $id_pub;

        return $this;
    }

    public function getTitrePub(): ?string
    {
        return $this->titre_pub;
    }

    public function setTitrePub(string $titre_pub): self
    {
        $this->titre_pub = $titre_pub;

        return $this;
    }

    public function getDescriptionPub(): ?string
    {
        return $this->description_pub;
    }

    public function setDescriptionPub(string $description_pub): self
    {
        $this->description_pub = $description_pub;

        return $this;
    }

    public function getContactPub(): ?int
    {
        return $this->contact_pub;
    }

    public function setContactPub(int $contact_pub): self
    {
        $this->contact_pub = $contact_pub;

        return $this;
    }

    public function getLocalisationPub(): ?string
    {
        return $this->localisation_pub;
    }

    public function setLocalisationPub(string $localisation_pub): self
    {
        $this->localisation_pub = $localisation_pub;

        return $this;
    }

    public function getIdA(): ?int
    {
        return $this->id_a;
    }

    public function setIdA(int $id_a): self
    {
        $this->id_a = $id_a;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(?enduser $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getImagePub(): ?string
    {
        return $this->image_pub;
    }

    public function setImagePub(string $image_pub): self
    {
        $this->image_pub = $image_pub;

        return $this;
    }

    public function getOffrePub(): ?string
    {
        return $this->offre_pub;
    }

    public function setOffrePub(string $offre_pub): self
    {
        $this->offre_pub = $offre_pub;

        return $this;
    }
}

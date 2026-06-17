<?php

namespace App\Entity;

use App\Repository\TransitLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransitLineRepository::class)]
#[ORM\Table(name: 'transit_line')]
#[ORM\UniqueConstraint(name: 'uniq_transit_line_code', fields: ['code'])]
class TransitLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $code = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    /** STIF line reference used by PRIM API, e.g. STIF:Line::C01742: */
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $primRef = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrimRef(): ?string
    {
        return $this->primRef;
    }

    public function setPrimRef(?string $primRef): static
    {
        $this->primRef = $primRef;

        return $this;
    }
}

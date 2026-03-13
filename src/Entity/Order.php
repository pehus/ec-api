<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255)]
    private string $shippingAddress;

    #[ORM\Column(nullable: true)]
    private ?float $geoLat = null;

    #[ORM\Column(nullable: true)]
    private ?float $geoLng = null;

    #[ORM\OneToMany(
        mappedBy: 'order',
        targetEntity: OrderItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getGeoLat(): ?float
    {
        return $this->geoLat;
    }

    public function setGeoLat(?float $geoLat): void
    {
        $this->geoLat = $geoLat;
    }

    public function getGeoLng(): ?float
    {
        return $this->geoLng;
    }

    public function setGeoLng(?float $geoLng): void
    {
        $this->geoLng = $geoLng;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
    }

    public function removeItem(OrderItem $item): void
    {
        if ($this->items->removeElement($item) && $item->getOrder() === $this) {
            $item->setOrder(null);
        }
    }
}

<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class description.
 * Second line
 *
 * @property-read Nette\Forms\Form $form
 */
#[ORM\Entity('App\Repository\AlbumRepository')]
#[ORM\Table(name: 'Albums')]
final class Album
{
	#[ORM\Column(name: 'id', type: null)]
	#[ORM\Id]
	public int $id;

	#[ORM\Column(name: 'albumRoot', type: null)]
	public int $albumRoot;

	#[ORM\Column(name: 'relativePath', type: null)]
	public string $relativePath;

	#[ORM\Column(name: 'date', type: 'date_immutable')]
	public ?\DateTimeInterface $date;

	#[ORM\Column(name: 'caption', type: null)]
	public string $caption;

	#[ORM\Column(name: 'collection', type: null)]
	public string $collection;

	#[ORM\Column(name: 'icon', type: null)]
	public int $icon;

	#[ORM\Column(name: 'modificationDate', type: 'datetime_immutable')]
	public ?\DateTimeInterface $modificationDate;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AlbumRoot $root = null;

    public function getRoot(): ?AlbumRoot
    {
        return $this->root;
    }

    public function setRoot(?AlbumRoot $root): static
    {
        $this->root = $root;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\AlbumRootRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class description.
 * Second line
 *
 * @property-read Nette\Forms\Form $form
 */
#[ORM\Entity('App\Repository\AlbumRootRepository')]
#[ORM\Table(name: 'AlbumRoots')]
final class AlbumRoot
{
	#[ORM\Column(name: 'id', type: null)]
	#[ORM\Id]
	public int $id;

	#[ORM\Column(name: 'label', type: null)]
	public string $label;

	#[ORM\Column(name: 'status', type: null)]
	public int $status;

	#[ORM\Column(name: 'type', type: null)]
	public int $type;

	#[ORM\Column(name: 'identifier', type: null)]
	public string $identifier;

	#[ORM\Column(name: 'specificPath', type: null)]
	public string $specificPath;

	#[ORM\Column(name: 'caseSensitivity', type: null)]
	public int $caseSensitivity;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'root')]
    private Collection $albums;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setRoot($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getRoot() === $this) {
                $album->setRoot(null);
            }
        }

        return $this;
    }
}

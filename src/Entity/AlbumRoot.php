<?php

namespace App\Entity;

use App\Repository\AlbumRootRepository;
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
}

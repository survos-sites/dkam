<?php

namespace App\Entity;

use App\Repository\AlbumRootsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class description.
 * Second line
 *
 * @property-read Nette\Forms\Form $form
 */
#[ORM\Entity('App\Repository\AlbumRootsRepository')]
#[ORM\Table(name: 'AlbumRoots')]
final class AlbumRoots
{
	#[ORM\Column]
	#[ORM\Id]
	public int $id;

	#[ORM\Column]
	public string $label;

	#[ORM\Column]
	public int $status;

	#[ORM\Column]
	public int $type;

	#[ORM\Column]
	public string $identifier;

	#[ORM\Column]
	public string $specificPath;

	#[ORM\Column]
	public int $caseSensitivity;


	#[\Foo\Cached(mode: true)]
	public function count(
		#[\Bar]
		$items,
	) {
	}
}

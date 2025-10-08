<?php
// tests/Unit/FolderServiceTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\FolderService;
use App\Repositories\FolderRepository;

class FolderServiceTest extends TestCase
{
    private $folderRepositoryMock;
    private $folderService;

    protected function setUp(): void
    {
        // Créer un "mock" du FolderRepository.
        // On ne veut pas tester la base de données, juste la logique du service.
        $this->folderRepositoryMock = $this->createMock(FolderRepository::class);

        // Instancier le service avec le mock.
        // Note: Cela requiert de modifier légèrement le constructeur de FolderService
        // pour permettre l'injection de dépendances, ce qui est une meilleure pratique.
        $this->folderService = new FolderService($this->folderRepositoryMock);
    }

    /**
     * @test
     */
    public function it_creates_a_folder_with_a_valid_name()
    {
        $folderName = "Nouveau Dossier";
        $parentId = null;
        $expectedResult = ['id' => 1, 'name' => $folderName, 'parent_id' => $parentId];

        // On configure le mock pour qu'il retourne un résultat attendu
        // quand sa méthode 'create' est appelée avec les bons arguments.
        $this->folderRepositoryMock->expects($this->once())
            ->method('create')
            ->with($folderName, $parentId)
            ->willReturn($expectedResult);

        $result = $this->folderService->createFolder($folderName, $parentId);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_creating_a_folder_with_an_empty_name()
    {
        // On s'attend à ce qu'une exception soit levée.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Le nom du dossier ne peut pas être vide.");

        // On appelle la méthode avec un nom vide.
        $this->folderService->createFolder("   ", null);

        // Le test échouera si aucune exception n'est levée.
    }
}

<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{

    public function __construct(private readonly string $targetDirectory, private readonly SluggerInterface $slugger) {}

    /**
     * Upload un fichier et retourne le nom unique  du fichier
     * 
     * @param UploadedFile $file
     * @param string $subdirectory - Sous dossier ex: 'challenges', 'avatars'
     * @return string le nom du fichier uploader 
     * @throws FileException
     */

    public function upload(UploadedFile $file, ?string $subdirectory = null): string
    {
        // validdation du type MIME

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/avif'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new FileException(
                sprintf(
                    "type de fichier non autorisé. type acceptés: %s",
                    implode(', ', $allowedMimeTypes)
                )
            );
        }

        // validation de la taille (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB en bytes
        if ($file->getSize() > $maxSize) {
            throw new FileException("Le fichier dépasse la taille maximale autorisée de 5MB.");
        }

        // generation d'un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;

        // determination du chemin de destination 

        $uploadPath = $this->targetDirectory;
        if ($subdirectory) {
            $uploadPath = $this->targetDirectory . '/' . $subdirectory;
        }

        // creation du dossier s'il n'existe pas
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0777, true) && !is_dir($uploadPath)) {
                throw new FileException("Impossible de créer le dossier d'upload: " . $uploadPath);
            }
        }

        // verifier que le dossier est accesible en ecriture
        if (!is_writable($uploadPath)) {
            throw new FileException("Le dossier d'upload n'est pas accessible en écriture: " . $uploadPath);
        }

        // deplacer le fichier vers le dossier de destination
        try {
            $file->move($uploadPath, $newFilename);
        } catch (FileException $e) {
            throw new FileException("Erreur lors du déplacement du fichier: " . $e->getMessage());
        }

        // retourner le chemin avec le prefixe /uploads/ 
        if($subdirectory ) {
            return '/uploads/' . $subdirectory . '/' . $newFilename;
        } 
        return '/uploads/' . $newFilename;
        
        
    }

    /**
     * Supprime un fichier
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return bool True si le fichier a été supprimé, false sinon
     */
    public function delete(string $filename): bool
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        $filePath = $this->targetDirectory . '/' . $relativePath;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Retourne le chemin complet du fichier
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return string Chemin complet
     */
    public function getFilePath(string $filename): string
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        return $this->targetDirectory . '/' . $relativePath;
    }

    /**
     * Vérifie si un fichier existe
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        return file_exists($this->targetDirectory . '/' . $relativePath);
    }

    /**
     * Retire le préfixe /uploads/ d'un chemin si présent
     *
     * @param string $path Chemin avec ou sans préfixe /uploads/
     * @return string Chemin relatif sans préfixe
     */
    private function getRelativePath(string $path): string
    {
        // Retirer le préfixe /uploads/ si présent
        if (str_starts_with($path, '/uploads/')) {
            return substr($path, strlen('/uploads/'));
        }
        return $path;
    }
}

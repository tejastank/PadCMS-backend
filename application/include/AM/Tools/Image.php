<?php
/**
 * @file
 * AM_Tools_Image class definition.
 *
 * LICENSE
 *
 * This software is governed by the CeCILL-C  license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-C
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-C license and that you accept its terms.
 *
 * @author Copyright (c) PadCMS (http://www.padcms.net)
 * @version $DOXY_VERSION
 */

/**
 * @defgroup AM_Tools
 */

/**
 * @ingroup AM_Tools
 */
class AM_Tools_Image
{
    /**
     * Resize image and save
     * @param string $sSrc Path to source image
     * @param string $sDst Path to output image
     * @param integer $iWidth Width of output image
     * @param integer $iHeight Height of output image
     * @param string $sMode Transformation mode
     */
    public static function resizeImage($sSrc, $sDst, $iWidth, $iHeight, $sMode = "in")
    {
        if (empty($sDst)) {
          $sDst = $sSrc;
        }

        if ($sMode == 'noresize') {
            return AM_Tools_Standard::getInstance()->copy($sSrc, $sDst);
        }

        $rSrcImage = self::loadImage($sSrc);
        if (!$rSrcImage) {
            return false;
        }
        $sImageType = self::getImageType($sSrc);
        $iSrcWidth  = imagesx($rSrcImage);
        $iSrcHeight = imagesy($rSrcImage);
        switch ($sMode) {
            case "force":
                if ($iSrcWidth == $iWidth && $iSrcHeight == $iHeight) {
                    return @copy($sSrc, $sDst);
                }
                $rDstImage = imagecreatetruecolor($iWidth, $iHeight);
                self::ImageCopyResampled($sImageType, $rDstImage, $rSrcImage,0, 0, 0, 0, $iWidth, $iHeight, $iSrcWidth, $iSrcHeight);
                break;
            case "width":
                $iDstWidth = $iWidth;
                $iDstHeight = round($iSrcHeight / $iSrcWidth * $iDstWidth);
                if ($iSrcWidth == $iWidth && $iSrcHeight == $iHeight) {
                    return @copy($sSrc, $sDst);
                }
                $rDstImage = imagecreatetruecolor($iDstWidth, $iDstHeight);
                self::ImageCopyResampled($sImageType, $rDstImage, $rSrcImage,0, 0, 0, 0, $iDstWidth, $iDstHeight, $iSrcWidth, $iSrcHeight);
                break;
            case "height":
                $iDstHeight = $iHeight;
                $iDstWidth = round($iSrcWidth / $iSrcHeight * $iDstHeight);
                if ($iSrcWidth == $iWidth && $iSrcHeight == $iHeight) {
                    return @copy($sSrc, $sDst);
                }
                $rDstImage = imagecreatetruecolor($iDstWidth, $iDstHeight);
                self::ImageCopyResampled($sImageType, $rDstImage, $rSrcImage,0, 0, 0, 0, $iDstWidth, $iDstHeight, $iSrcWidth, $iSrcHeight);
                break;
            case "out":
                $fRatioWidth  = $iSrcWidth / $iWidth;
                $fRatioHeight = $iSrcHeight / $iHeight;
                $fRatio       = min($fRatioHeight, $fRatioWidth);
                $iDstWidth    = $iWidth;
                $iDstHeight   = $iHeight;
                $iX           = round(abs($iSrcWidth - $iDstWidth * $fRatio) / 2);
                $iY           = round(abs($iSrcHeight - $iDstHeight * $fRatio) / 2);
                if ($iX == 0 && $iY == 0 && $iDstWidth == $iSrcWidth && $iDstHeight == $iSrcHeight) {
                    return @copy($sSrc, $sDst);
                }
                $rDstImage = imagecreatetruecolor($iDstWidth, $iDstHeight);
                self::ImageCopyResampled($sImageType, $rDstImage, $rSrcImage, 0, 0, 0, 0, $iDstWidth, $iDstHeight, ($iSrcWidth - 2 * $iX), ($iSrcHeight - 2 * $iY));
                break;
            case "in":
            default:
                $fRatioWidth = $iSrcWidth / $iWidth;
                $fRatioHeight = $iSrcHeight / $iHeight;
                $fRatio = max($fRatioHeight, $fRatioWidth);
                $iDstWidth = $iSrcWidth / $fRatio;
                $iDstHeight = $iSrcHeight / $fRatio;
                if (round($iSrcWidth) == $iWidth && round($iSrcHeight) == $iHeight) {
                    return @copy($sSrc, $sDst);
                }

                $rDstImage = imagecreatetruecolor($iDstWidth, $iDstHeight);
                self::ImageCopyResampled($sImageType, $rDstImage, $rSrcImage, 0, 0, 0, 0, $iDstWidth, $iDstHeight, $iSrcWidth, $iSrcHeight);
                break;
        }

        return self::saveImage($sImageType, $rDstImage, $sDst);
    }

    /**
     * Parse the results of
     *
     * @param string $sPath
     * @return false | string Image type
     */
    public static function getImageType($sPath)
    {
        list($iWidth, $iHeight, $sType, $sAttr) = @getimagesize($sPath);

        if (!isset($iWidth) || $iWidth <= 0) {
            return false;
        }

        $sType = image_type_to_extension($sType, false);

        return $sType;
    }

    /**
     * Determinate type of image by extension and load image
     *
     * @param string $sPath Path to the image
     * @return resource Image link
     */
    public static function loadImage($sPath) {
        $sType = self::getImageType($sPath);

        switch ($sType) {
            case "png":
                return @imagecreatefrompng($sPath);
            case "jpg":
            case "jpeg":
                return @imagecreatefromjpeg($sPath);
            case "gif":
                return @imagecreatefromgif($sPath);
        }

        return false;
    }

    /**
     * Determinate type of image by extension and save image
     *
     * @param string $sType
     * @param resource $rImg
     * @param string $sPath
     * @return resource Image link
     */
    public static function saveImage($sType, $rImg, $sPath) {
        $extension = strtolower(pathinfo($sPath, PATHINFO_EXTENSION));
        switch ($extension) {
            case "png":
                return @imagepng($rImg, $sPath);
            case "jpg":
            case "jpeg":
                return @imagejpeg($rImg, $sPath);
            case "gif":
                return @imagegif($rImg, $sPath);
        }
        return false;
    }

    /**
     *
     * @param resource $rDstImage
     * @param resource $rSrcImage
     * @param int $iDstX
     * @param int $iDstY
     * @param int $iSrcX
     * @param int $iSrcY
     * @param int $iDstW
     * @param int $iDstH
     * @param int $iSrcW
     * @param int $iSrcH
     */
    public static function ImageCopyResampled($sType, $rDstImage, $rSrcImage, $iDstX, $iDstY, $iSrcX, $iSrcY, $iDstW, $iDstH, $iSrcW, $iSrcH)
    {
        // preserve transparency
        if ($sType == "gif" || $sType == "png") {
            imagecolortransparent($rDstImage, imagecolorallocatealpha($rDstImage, 0, 0, 0, 127));
            imagealphablending($rDstImage, false);
            imagesavealpha($rDstImage, true);
        }

        return imagecopyresampled($rDstImage, $rSrcImage, $iDstX, $iDstY, $iSrcX, $iSrcY, $iDstW, $iDstH, $iSrcW, $iSrcH);
    }

    /**
     * http://www.imagemagick.org/Usage/crop/#crop_equal
     * @param string $sImagePath
     * @return void
     * @throws AM_Exception
     */
    public static function cropImage($sImagePath)
    {
        $sTempDir = AM_Handler_Temp::getInstance()->getDir();

        $sArchivePath = pathinfo($sImagePath, PATHINFO_DIRNAME);
        $sArchiveName = pathinfo($sImagePath, PATHINFO_FILENAME);

        $sCmd = sprintf('convert %s -crop 256x256 -set filename:title "%%[fx:page.y/256+1]_%%[fx:page.x/256+1]" +repage  +adjoin %s/"resource_%%[filename:title].png"', $sImagePath, $sTempDir);

        AM_Tools_Standard::getInstance()->passthru($sCmd);

        $aFiles = AM_Tools_Finder::type('file')
                ->name('resource_*.png')
                ->sort_by_name()
                ->in($sTempDir);

        $sZipPath         = $sArchivePath . DIRECTORY_SEPARATOR . $sArchiveName .'.zip';
        $oZip             = new ZipArchive();
        $rArchiveResource = $oZip->open($sZipPath, ZIPARCHIVE::CREATE);

        if ($rArchiveResource !== true) {
            throw new AM_Exception('I/O error. Can\'t create zip file: ' . $sZipPath);
        }
        foreach ($aFiles as $sFile) {
            $oZip->addFile($sFile, pathinfo($sFile, PATHINFO_BASENAME));
        }
        $oZip->close();
    }
}
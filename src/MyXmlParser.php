<?php

/**
 * Fonctions utiles
 *
 * @author Camille Khalaghi
 */
class MyXmlParser {

    /**
     * Extrait le contenu du premier tag trouve
     *
     * @param string $xml       Contenu Xml
     * @param string $tag       Tag dont le contenu est a extraire
     * @param int    $offset    (optionnel) Position ou l'on doit commencer a chercher. 0 par defaut.
     * @param bool   $withTag   (optionnel) Si true, retourne le contenu avec le tag. false par defaut.
     * @param bool   $attributeValue   (optionnel) valeur d'un attribut a chercher dans le tag. ex : 'class="foo-bar"' ou 'foo-bar'
     * @return array        contenu du tag + Premiere position apres le tag de fin
     */
    static public function getXmlTagContent($xml, $tag, $offset = 0, $withTag=false, $attributeValue=null)
    {
        // initialisation
        $tagDeb = "<$tag";
        $tagFin = "</$tag";
        if (strpos($tag, '>') <= 0)
            $tagFin .= '>';
        $lenMax = strlen($xml);

        // prise de la position du tag de depart
        $posDeb1 = self::getXmlTagStartPosition($xml, $lenMax, $tag, $offset, $attributeValue);
        if ($posDeb1 == $lenMax)
        {
            // tag non-trouve
            unset($posDeb1, $tagDeb, $tagFin);
            return (array('', $lenMax));
        }
        $posDeb2 = strpos($xml, '>', $posDeb1);


        // gestion du tag auto-fermant
        if ($xml[$posDeb2 - 2] == '/') {
            if ($withTag === false) {
                unset($tagDeb, $tagFin, $lenMax, $posDeb1);
                return (array('', $posDeb2));
            }
            $res = substr($xml, $posDeb1, ($posDeb2 - $posDeb1));
            unset($tagDeb, $tagFin, $lenMax, $posDeb1);
            return (array($res, $posDeb2));
        }

        // prise de la position du tag de  fin
        {
            $depth = 1;
            $curPos = $posDeb2;
            while (true)
            {
                // position du tag de fin
                $endTagPos = strpos($xml, $tagFin, $curPos);
                // si on arrive a la fin du document
                if ($endTagPos === FALSE || $curPos >= $lenMax)
                {
                    $endTagPos = $lenMax;
                    if ($withTag === true)
                        $res = substr($xml, $posDeb1, ($endTagPos - $posDeb1));
                    else
                        $res = substr($xml, $posDeb2, ($endTagPos - $posDeb2));
                    return (array($res, $endTagPos));
                }
                // position du prochain tag d'ouverture
                $newTagPos = self::getXmlTagStartPosition($xml, $lenMax, $tag, $curPos);
                if ($newTagPos === FALSE)
                    $newTagPos = $lenMax;
                // determination de la nouvelle position
                if ($newTagPos < $endTagPos) {
                    $curPos = $newTagPos + 1;
                    $depth++;
                } elseif ($endTagPos < $newTagPos) {
                    $depth--;
                    if ($depth <= 0) {
                        $curPos = $endTagPos;
                        break;
                    }
                    $curPos = $endTagPos + 1;
                }
            }
            $posFin1 = $curPos;
            $posFin2 = strpos($xml, '>', ($posFin1 + 1)) + 1;
        }

        // prise du contenu
        {
            if ($withTag === true)
                $res = substr($xml, $posDeb1, ($posFin2 - $posDeb1));
            else
                $res = substr($xml, $posDeb2+1, ($posFin1 - $posDeb2 - 1));
            $res = trim($res);
        }

        // retour
        unset($tagDeb, $posDeb1, $posDeb2, $tagFin, $posFin1, $depth, $curPos, $endTagPos, $lenMax, $newTagPos);
        return (array($res, $posFin2));
    }


    /**
     * Extrait la position du premier tag trouve
     *
     * @param string $xml       Contenu Xml
     * @param   int     $lenMax Longueur de la chaine $xml
     * @param string $tag       Tag dont le contenu est a extraire
     * @param int    $offset    (optionnel) Position ou l'on doit commencer a chercher. 0 par defaut.
     * @param bool   $withTag   (optionnel) Si true, retourne le contenu avec le tag. false par defaut.
     * @param bool   $attributeValue   (optionnel) valeur d'un attribut a chercher dans le tag. ex : 'class="foo-bar"' ou 'foo-bar'
     * @return int        position de depart du tag recherche. $lenMax si non trouve.
     */
    static public function getXmlTagStartPosition($xml, $lenMax, $tag, $offset = 0, $attributeValue=null) {
        // initialisations
        $curPos = $offset;
        $tagDeb = "<$tag";
        $tagDebLen = strlen($tagDeb);

        // recherche
        while (true)
        {
            $posDeb = strpos($xml, $tagDeb, $curPos);
            // si on a pas trouve le tag a la fin du fichier, on quitte
            if ($posDeb === FALSE) {
                $curPos = $lenMax;
                break;
            }
            // verification de faux tag (ex : on cherche <job et on trouve <jobType )
            $char = $posDeb + $tagDebLen;
            if ($xml[$char] == ' ' || $xml[$char] == "\t" || $xml[$char] == "\n") {
                // on est peut etre tombe sur le bon
                $posFin = strpos($xml, '>', $char);
            } elseif ($xml[$char] == '>') {
                $posFin = $char;
            } else {
                // on est sur un faux tag => on recommance la recherche a partir de la position actuelle
                $curPos = $char;
                unset($posDeb, $char, $posFin);
                continue;
            }
            unset($char);

            // cas du '>' non trouve (extremment rare)
            if ($posFin === false) {
                $curPos = $lenMax;
                break;
            }

            // verif du $attributeValue
            {
                if (empty($attributeValue)) {
                    $curPos = $posDeb;
                    break;
                }

                $myTag = substr($xml, $posDeb, ($posFin - $posDeb + 1));
                if (strpos($myTag, $attributeValue) === false) {
                    // le $attributeValue n'est pas dans le tag recherche. Redemarrage de la recherche a partir de la position actuelle
                    $curPos = $posFin;
                    unset($posDeb, $posFin, $myTag);
                    continue;
                }
                // on est bon
                $curPos = $posDeb;
                unset($myTag);
                break;
            }
        }

        // retour
        unset($posDeb, $posFin, $myTag,$tagDeb, $tagDebLen);
        return ($curPos);
    }


    /**
     * Recupere le contenu d'un parametre d'un tag.
     * @param   string  $xml        Chaine de recherche. Ex : "<mytag myparam="mycontent"></mytag>"
     * @param   string  $tag        Nom du tag
     * @param   string  $paramName  Nom du parametre
     * @param   string  $offset     (optionnel) Position a partir de laquelle rechercher.
     * @return  array   Structure de donnees (contenu + position de fin de tag [pour seconde recherche])
     */
    static public function getTagParameter($xml, $tag, $paramName, $offset = 0)
    {
        // initialisation
        $tagDeb = "<$tag";
        $tagFin = "</$tag>";

        // prise de la position du tag de depart
        {
            $posDeb1 = strpos($xml, $tagDeb, $offset);
            if ($posDeb1 === FALSE)
                return (array('', $offset));
            $posDeb2 = strpos($xml, '>', ($posDeb1+1) ) + 1;
        }
        // prise de la position du tag de  fin
        $posFin1 = strpos($xml, $tagFin, $posDeb2);
        $posFin2 = strpos($xml, '>', ($posFin1+1)) + 1;

        // prise du tag d'ouverture
        $tagStr = substr($xml, $posDeb1, ($posDeb2 - $posDeb1));

        // prise des positions du parametre
        $posTag1 = strpos($tagStr, $paramName);
        if ($posTag1 === false)
            return (array('', $posFin2));
        $posTag1 = strpos($tagStr, "\"", $posTag1) + 1;
        $posTag2 = strpos($tagStr, "\"", $posTag1);

        // prise du contenu
        $res = substr($tagStr, $posTag1, ($posTag2-$posTag1) );
        $res = trim($res);

        // retour
        unset($tagDeb, $posDeb1, $posDeb2, $tagFin, $posFin1, $posTag1, $posTag2);
        return (array($res, $posFin2));
    }

    /**
     * Extrait le contenu d'un tag CDATA
     *
     * @param string $str Contenu Xml sous la forme <![CDATA[content]]>
     * @return string       contenu du tag CData
     */
    static public function getCdataContent($str)
    {
        // Position du premier caractere de contenu
        {
            $pos1 = strpos($str, '<![CDATA[');
            if ($pos1 === false)
                return ('');
            $pos1 += 9;
        }

        // Position du dernier caractere de contenu
        $pos2 = strpos($str, ']]>');

        // Extraction
        $res = substr($str, $pos1, ($pos2 - $pos1));

        // retour
        unset($pos1, $pos2);
        return ($res);
    }

}



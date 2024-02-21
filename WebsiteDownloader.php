<?php

class WebsiteDownloader {

    private $domain;
    private $outputDir;

    public function __construct($args) {
        if (! isset($args['domain'])) {
            throw new InvalidArgumentException("Domain is required.");
        }

        if (! isset($args['output_dir'])) {
            throw new InvalidArgumentException("Output Directory is required.");
        }

        $this->domain = $args['domain'];
        $this->outputDir = $args['output_dir'];

        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    public function download($asset = 'all') {
        switch ($asset) {
            case 'all':
                $this->downloadHtml();
                $this->downloadCSSAndFavIcons();
                $this->downloadJS();     
                $this->downloadImages();
                break;
            case 'html':
                $this->downloadHtml();
                break;
            case 'css':
                $this->downloadHtml();
                $this->downloadCSSAndFavIcons();
                $this->removeHtml();
                break;
            case 'js':
                $this->downloadHtml();
                $this->downloadJS();
                $this->removeHtml();
                break;
            case 'img':
                $this->downloadHtml();
                $this->downloadImages();
                $this->removeHtml();
                break;
            default:
                throw new InvalidArgumentException("Asset $asset not supported.");
        }
    }

    private function removeHtml(){
        $files = glob ("$this->outputDir/*.html");
        foreach ($files as $file){
            unlink($file);
        }
       
        $files = glob ("$this->outputDir/*.{jpg,png,gif}", GLOB_BRACE);
        foreach ($files as $file){
            unlink($file);
        }
    }

    private function downloadHtml(){
        echo "---------------------------------------\n";
        echo "Downloading HTML files...\n";
        echo "---------------------------------------\n";

        $content = file_get_contents($this->domain);
        $dom = new DOMDocument();
        @$dom->loadHTML($content);

        $links = $dom->getElementsByTagName("a");

        foreach ($links as $link) {
            $href = $link->getAttribute("href");
        
            if (!strpos($href, "http")) {
              $urlParts = explode("//", $href);
              $uniqueParts = array_unique($urlParts);
              $fixedUrl = implode("//", $uniqueParts);
              
              $href = ($this->startsWith ($fixedUrl, [$this->domain])) ? $fixedUrl : "$this->domain/$href"; // Handle relative links
            }

            if (!empty($href) && $href != "/") {
              $filename = basename($href);
              
              if (strpos($filename, ".") === false || strpos($filename, ".co.za") !== false || strpos($filename, ".com") !== false) $filename = "$filename.html";
          
              if (! $this->startsWith ($filename, ['#'])) {
                $filename = str_replace('?', '.', $filename);
                $this->downloadFile($this->outputDir, $href, $filename);
                echo "$href\n";
              }
            }
        }

        echo "---------------------------------------\n";
        echo "Downloading HTML Completed!\n";
        echo "---------------------------------------\n";
    }

    public function downloadAssetFromURL($href){
        if (! empty($href) && $href != "/"){
            $filename = basename($href);
            $this->downloadFile($this->outputDir, $href, $filename);

            $file = "$this->outputDir/$filename";

            echo "---------------------------------------\n";
            echo "File Downloaded: $file\n";
            echo "---------------------------------------\n";
        }else{
            echo "---------------------------------------\n";
            echo "Error: Invalid URL provided.\n";
            echo "---------------------------------------\n";
        }
    }

    private function downloadAssetsFromDOM($files, $tagName, $attribute){
        foreach ($files as $file){
            $content = file_get_contents($file);

            $dom = new DOMDocument();
            @$dom->loadHTML($content);
            $links = $dom->getElementsByTagName($tagName);
            
            foreach ($links as $link) {
                $href = $link->getAttribute($attribute);
                
                if (! $this->startsWith ($href, ['http', '//'])) {
                    $href = "$this->domain/$href"; // Handle relative links
                    
                    if (! empty ($href) && $href != "/"){
                        $dirname = dirname($href);
                        $dirname = str_replace($this->domain, '', $dirname);
                        
                        $filename = basename($href);
                        if (! empty($dirname) && ! $this->startsWith ($dirname, ['https'])){
                            $fullPath = "$this->outputDir/$dirname";
                        
                            if (!file_exists($fullPath)){
                                if (! mkdir($fullPath, 0777, true)) { // Use true for recursive creation
                                    die("Failed to create folders: $fullPath"); // Handle potential errors
                                }
                            }

                            $this->downloadFile($fullPath, $href, $filename);
                        }else{
                            $this->downloadFile($this->outputDir, $href, $filename);
                        }

                        echo "$href\n";
                    }
                }

                if ($this->startsWith ($href, ['http'])){
                    $dirname = dirname($href);
                    $dirname = str_replace($this->domain, '', $dirname);
                    
                    $filename = basename($href);
                    if (! empty($dirname) && ! $this->startsWith ($dirname, ['https'])){
                        $fullPath = "$this->outputDir/$dirname";
                    
                        if (!file_exists($fullPath)){
                            if (! mkdir($fullPath, 0777, true)) { // Use true for recursive creation
                                die("Failed to create folders: $fullPath"); // Handle potential errors
                            }
                        }

                        $this->downloadFile($fullPath, $href, $filename);
                    }else{
                        $this->downloadFile($this->outputDir, $href, $filename);
                    }

                    echo "$href\n";
                }
            }
        }
    }

    private function downloadEmbededHTML(){
        echo "---------------------------------------\n";
        echo "Downloading Embeded HTML files...\n";
        echo "---------------------------------------\n";

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'a', 'href');

        echo "---------------------------------------\n";
        echo "Downloading Embeded HTML files Complete!\n";
        echo "---------------------------------------\n";
    }

    private function downloadCSSAndFavIcons(){
        echo "---------------------------------------\n";
        echo "Downloading CSS and Image files...\n";
        echo "---------------------------------------\n";

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'link', 'href');

        echo "---------------------------------------\n";
        echo "Downloading CSS and Image files Complete!\n";
        echo "---------------------------------------\n";
    }

    private function downloadJS(){
        echo "---------------------------------------\n";
        echo "Downloading JS files...\n";
        echo "---------------------------------------\n";

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'script', 'src');

        echo "---------------------------------------\n";
        echo "Downloading JS files Complete!\n";
        echo "---------------------------------------\n";
    }

    private function downloadCSS(){
        
    }

    private function downloadImages(){
        echo "---------------------------------------\n";
        echo "Downloading Image files...\n";
        echo "---------------------------------------\n";

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'img', 'src');

        echo "---------------------------------------\n";
        echo "Downloading Image files Complete!\n";
        echo "---------------------------------------\n";
    }

    private function startsWith($word, $startsWith) {
        $isStartWith = false;
    
        foreach ($startsWith as $char){
            if (substr($word, 0, strlen($char)) === $char){
                $isStartWith = true;
                break;
            }
        }
        return $isStartWith;
    }

    private function downloadFile($directory, $url, $filename) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        
        file_put_contents($directory . '/' . $filename, $content);
    }
}

?>
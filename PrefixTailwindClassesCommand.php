<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class PrefixTailwindClassesCommand extends Command
{
    protected $signature = 'tailwind:prefix-classes 
                            {--path=resources/views : Path to Blade files}
                            {--prefix=tw- : Prefix to add to Tailwind classes}';
    protected $description = 'Prefix Tailwind classes with custom prefix in Blade view files';
    public function handle()
    {
        $path = base_path($this->option('path'));
        $prefix = $this->option('prefix');
        if (!is_dir($path)) {
            $this->error("Invalid path: $path");
            return;
        }
        $this->info("📁 Scanning: $path");
        $this->info("🔖 Prefix: $prefix");
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $filePath = $file->getRealPath();
            $content = file_get_contents($filePath);
            $updated = preg_replace_callback('/class\s*=\s*["\']([^"\']+)["\']/', function ($matches) use ($prefix) {
                $classes = preg_split('/\s+/', $matches[1]);
                $prefixed = array_map(function ($class) use ($prefix) {
                    // Avoid double prefix
                    return str_starts_with($class, $prefix) ? $class : $prefix . $class;
                }, $classes);
                return 'class="' . implode(' ', $prefixed) . '"';
            }, $content);
            if ($updated !== $content) {
                file_put_contents($filePath, $updated);
                $this->info("✅ Updated: $filePath");
            }
        }
        $this->info("🎉 Done prefixing Tailwind classes.");
    }
}

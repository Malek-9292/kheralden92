<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class MenuProductImageMatcher
{
    /**
     * @return list<array{stem:string,path:string,filename:string}>
     */
    public function catalogImages(?string $directory = null): array
    {
        $directory ??= public_path('images/menu');
        if (! is_dir($directory)) {
            return [];
        }

        $images = [];
        foreach (File::files($directory) as $file) {
            if (! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $filename = $file->getFilename();
            $stem = pathinfo($filename, PATHINFO_FILENAME);
            $images[] = [
                'stem' => $stem,
                'path' => $file->getPathname(),
                'filename' => $filename,
            ];
        }

        return $images;
    }

    /**
     * Pick the best menu image for a product name, or null if none match.
     *
     * @param  list<array{stem:string,path:string,filename:string}>  $images
     * @return array{stem:string,path:string,filename:string,score:int}|null
     */
    public function bestMatch(string $productName, array $images): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach ($images as $image) {
            $score = $this->score($productName, $image['stem']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $image + ['score' => $score];
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    public function score(string $productName, string $imageStem): int
    {
        $product = $this->normalize($productName);
        $stem = $this->normalize($imageStem);

        if ($product === '' || $stem === '') {
            return 0;
        }

        $best = 0;
        foreach ($this->matchStems($stem) as $candidate) {
            if ($product === $candidate) {
                $best = max($best, 10000 + (mb_strlen($candidate) * 10));

                continue;
            }

            // Prefer longer stems contained in the product name (variants / sizes).
            if (mb_strpos($product, $candidate) !== false) {
                $best = max($best, 1000 + (mb_strlen($candidate) * 10));
            }
        }

        return $best;
    }

    /**
     * @return list<string>
     */
    private function matchStems(string $normalizedStem): array
    {
        $stems = [$normalizedStem];

        // Allow plate/meal variants that share the dish base (كفتة، كباب، …).
        $bases = [
            'كفتة بالطماطم' => 'كفتة',
            'كبة دجاج' => 'كبة',
        ];

        if (isset($bases[$normalizedStem])) {
            $stems[] = $bases[$normalizedStem];
        }

        return $stems;
    }

    public function normalize(string $value): string
    {
        $value = trim($value);
        $value = str_replace(["\u{0640}"], '', $value); // tatweel

        $replacements = [
            'تكا' => 'تكة',
            'تكه' => 'تكة',
            'بالطماط' => 'بالطماطم',
            'تقانق' => 'نقانق',
            'مسحب' => 'مسحوب',
            'حلومي' => 'حلوم',
            'كبدة' => 'كبة',
            'بالصلصة' => 'بالطماطم',
            'بالجبن' => 'جبن',
        ];

        foreach ($replacements as $from => $to) {
            $value = str_replace($from, $to, $value);
        }

        $value = str_replace(['+', '＋'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use App\Models\Product;

class GenerateMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um cardápio em DOCX baseado no estoque';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Gerando cardápio...');

        $phpWord = new PhpWord();

        // Configurar fonte padrão
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(12);

        // Caminho do logo
        $logoPath = public_path('Jaque.png');
        if (!file_exists($logoPath)) {
            $this->warn("Logo não encontrado em $logoPath. Tentando legacy...");
            $logoPath = base_path('legacy/Jaque.png');
        }

        // Adicionar Seção
        // Tentar adicionar imagem de fundo se possível, mas o suporte varia.
        // Vamos usar um header com a imagem para garantir que apareça em todas as páginas,
        // ou apenas na primeira página se for um cardápio simples.
        // O usuário pediu "imagem do logo no fundo". Watermark é o mais próximo disso.
        
        $sectionStyle = [];
        // Watermark não é trivial no PHPWord 1.1.0 sem patches as vezes.
        // Vamos tentar adicionar como Header image para ficar no topo.
        
        $section = $phpWord->addSection($sectionStyle);
        
        // Adicionar Logo Centralizado
        if (file_exists($logoPath)) {
            $header = $section->addHeader();
            $header->addImage($logoPath, [
                'width' => 100,
                'height' => 100,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
            // Tentar watermark (pode não funcionar em todas as versões/visualizadores)
            // $header->addWatermark($logoPath, ['marginTop' => 200, 'marginLeft' => 55]);
        }

        // Título Principal
        $section->addText('CARDÁPIO NA BRASA', ['bold' => true, 'size' => 24, 'color' => 'B22222'], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Buscar produtos ativos agrupados por categoria
        $products = Product::where('active', 1)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $styleTable = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80];
        $styleFirstRow = ['borderBottomSize' => 18, 'borderBottomColor' => '0000FF', 'bgColor' => '66BBFF'];
        
        // Definir estilos de tabela
        $phpWord->addTableStyle('MenuTable', $styleTable, $styleFirstRow);

        foreach ($products as $category => $items) {
            // Título da Categoria
            $section->addText(strtoupper($category), ['bold' => true, 'size' => 16, 'color' => '333333'], ['spaceBefore' => 240, 'spaceAfter' => 120]);

            // Tabela de Itens
            $table = $section->addTable('MenuTable');
            
            // Cabeçalho da Tabela (Opcional, talvez não precise para cardápio, mas vamos por)
            // $table->addRow();
            // $table->addCell(5000)->addText('Item', ['bold' => true]);
            // $table->addCell(2000)->addText('Preço', ['bold' => true]);

            foreach ($items as $item) {
                $table->addRow();
                
                // Nome do Produto
                $table->addCell(6000)->addText($item->name, ['bold' => true, 'size' => 12]);
                
                // Preço (Formatado)
                $priceFormatted = 'R$ ' . number_format($item->price, 2, ',', '.');
                $table->addCell(2000)->addText($priceFormatted, ['bold' => true, 'size' => 12], ['alignment' => 'right']);
            }
            
            $section->addTextBreak(1);
        }

        $fileName = 'cardapio_na_brasa.rtf';
        $path = public_path($fileName);
        
        // Usar RTF pois a extensão ZipArchive não está habilitada no PHP
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'RTF');
        $objWriter->save($path);

        $this->info("Cardápio gerado com sucesso em: $path");
        $this->info("Acesse: " . url($fileName));
    }
}

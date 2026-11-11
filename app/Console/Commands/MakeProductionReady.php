<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeProductionReady extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-production-ready {--force : Forçar a execução sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa todos os dados de produção (pedidos, despesas, fechamentos), mantendo apenas o estoque.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('TEM CERTEZA? Isso apagará TODO o histórico de vendas, despesas e fechamentos. Apenas os PRODUTOS serão mantidos.')) {
            $this->info('Operação cancelada.');
            return;
        }

        $this->info('Iniciando limpeza...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Limpa tabelas de movimentos
            $tables = ['order_items', 'orders', 'expense_items', 'expenses', 'closings'];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->info("Tabela '$table' limpa.");
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('Limpeza concluída com sucesso! O sistema está pronto para produção (apenas produtos mantidos).');

        } catch (\Exception $e) {
            $this->error('Erro ao limpar dados: ' . $e->getMessage());
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}

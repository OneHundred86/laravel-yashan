<?php

namespace Oh86\LaravelYashan\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Oh86\LaravelYashan\YSReservedWords;

class Comment extends Grammar
{
    use YSReservedWords;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * @param  Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set table and column comments.
     *
     * @param  YSBlueprint  $blueprint
     */
    public function setComments(YSBlueprint $blueprint)
    {
        $this->commentTable($blueprint);

        $this->fluentComments($blueprint);

        $this->commentColumns($blueprint);
    }

    /**
     * Run the comment on table statement.
     * Comment set by $table->comment = 'comment';.
     *
     * @param  YSBlueprint  $blueprint
     */
    private function commentTable(YSBlueprint $blueprint)
    {
        $table = $this->wrapValue($blueprint->getTable());

        if ($blueprint->comment != null) {
            $this->connection->statement("comment on table {$table} is '{$blueprint->comment}'");
        }
    }

    /**
     * Wrap reserved words.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        // return $this->isReserved($value) ? parent::wrapValue($value) : $value;
        return parent::wrapValue($value);
    }

    /**
     * Add comments set via fluent setter.
     * Comments set by $table->string('column')->comment('comment');.
     *
     * @param  YSBlueprint  $blueprint
     */
    private function fluentComments(YSBlueprint $blueprint)
    {
        foreach ($blueprint->getColumns() as $column) {
            if (isset($column['comment'])) {
                $this->commentColumn($blueprint->getTable(), $column['name'], $column['comment']);
            }
        }
    }

    /**
     * Run the comment on column statement.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  string  $comment
     */
    private function commentColumn($table, $column, $comment)
    {
        $table = $this->wrapValue($table);
        $table = $this->connection->getTablePrefix().$table;
        $column = $this->wrapValue($column);

        $this->connection->statement("comment on column {$table}.{$column} is '{$comment}'");
    }

    /**
     * Add comments on columns.
     * Comments set by $table->commentColumns = ['column' => 'comment'];.
     *
     * @param  YSBlueprint  $blueprint
     */
    private function commentColumns(YSBlueprint $blueprint)
    {
        foreach ($blueprint->commentColumns as $column => $comment) {
            $this->commentColumn($blueprint->getTable(), $column, $comment);
        }
    }
}

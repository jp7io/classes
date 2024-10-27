<?php

namespace Jp7\Laravel;

use Illuminate\Support\Str;

trait Routable
{
    public function getControllerBasename()
    {
        return $this->getStudly().'Controller';
    }

    // AEmpresa\PerfilController
    /*
    public function getControllerName() {
        $namespace = $this->getNamespace();
        return ($namespace ? $namespace . '\\' : '') . $this->getControllerBasename();
    }

    public function getNamespace() {
        $parent = $this->getParent();
        $namespace = array();
        while ($parent && !$parent->isRoot()) {
            $namespace[] = $parent->getStudly();
            $parent = $parent->getParent();
        }
        if ($this->type_id == 4178) {
            kd($namespace);
        }
        return implode($namespace, '\\');
    }
    */

    // a-empresa
    public function getSlug()
    {
        $nome = Str::slug($this->nome);
        if (is_numeric($nome)) {
            // verificar maneira de tratar isso
            $nome = 'list-'.$nome;
        }

        return substr($nome, 0, 32);
    }

    public function getStudly()
    {
        return Str::studly($this->getSlug());
    }

    public function isRoot()
    {
        return $this->type_id == '0';
    }

    public function getChildrenMenu()
    {
        return $this->children()->where('menu', true)->get();
    }
}

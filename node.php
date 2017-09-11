<?php

class MyTree extends Tree
{
    use Storage;

    public function createNode(Node $node, $parentNode = NULL)
    {
        if (null === $parentNode)
            return $this->create($node);

        return $this->createChild($node, $parentNode);
    }

    public function deleteNode(Node $node)
    {
        $this->delete($node);
    }

    public function attachNode(Node $node, Node $parent)
    {
        if (false === $this->search($this->getNodeKey($node)))
            throw new Exception('Node not found');
        if (false === $this->search($this->getNodeKey($parent)))
            throw new Exception('Parent Node not found');

        $this->move($node, $parent);
    }

    public function getNode($nodeName)
    {
        if (false !== $this->search($nodeName))
            return new Node($nodeName);

        throw new Exception('Node not found');
    }

    public function export()
    {
        return $this->getStorage();
    }

}

trait Storage
{
    /** @var array */
    public $storage = [];

    /**
     * @param Node $node
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function create(Node $node)
    {
        $key = $this->getNodeKey($node);
        if (isset($this->storage[$key]))
            throw new Exception('Object already exists');

        $this->storage[$key] = [];

        return true;
    }

    /**
     * @param Node $node
     * @param Node $parentNode
     *
     * @return boolean
     *
     * @throws Exception
     */
    protected function createChild($node, $parentNode)
    {
        return $this->search($this->getNodeKey($parentNode), function (array &$storage) use ($node) {

            $new_key = $this->getNodeKey($node);
            if (isset($storage[$new_key]))
                throw new Exception('Object already exists');

            $storage[$new_key] = [];

            return $storage;
        });
    }

    /**
     * @param Node $node
     * @param Node $parentNode
     *
     * @return bool
     */
    protected function move(Node $node, Node $parentNode)
    {
        $node_tree = $this->search($this->getNodeKey($node));
        $this->delete($node);

        return $this->search($this->getNodeKey($parentNode), function (array &$storage) use ($node, $node_tree) {

            $new_key = $this->getNodeKey($node);
            if (isset($storage[$new_key]))
                throw new Exception('Object already exists');

            $storage[$new_key] = $node_tree;

            return $storage;
        });

    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    protected function delete(Node $node)
    {
        return $this->search_parent($this->getNodeKey($node), function (array &$storage) use ($node) {

            $new_key = $this->getNodeKey($node);

            unset($storage[$new_key]);

            return $storage;
        });

    }

    /**
     * @param $name
     * @param callable $callback
     * @param array|null $subtree
     *
     * @return boolean | array
     */
    protected function search($name, $callback = null, &$subtree = null)
    {
        $is_first_level = null === $subtree;
        $is_found = false;

        if (null === $subtree)
            $subtree = $this->storage;

        foreach ($subtree as $key => &$array)
        {
            if ($key === $name)
            {
                $is_found = $array;

                if (null !== $callback)
                    $array = $callback($array);

                break;
            }

            if (empty($array))
                continue;

            $result = $this->search($name, $callback, $array);
            if (false !== $result)
            {
                $is_found = $result;
                break;
            }
        }

        if ($is_first_level)
            $this->updateStorage($subtree);

        return $is_found;
    }

    /**
     * @param $name
     * @param string $callback
     * @param array|null $subtree
     *
     * @return boolean
     */
    protected function search_parent($name, $callback = null, &$subtree = null)
    {
        $is_first_level = null === $subtree;
        $is_found = false;

        if (null === $subtree)
            $subtree = $this->storage;

        foreach ($subtree as $key => &$array)
        {
            if ($key === $name)
            {
                $is_found = $subtree;

                if (null !== $callback)
                    $array = $callback($subtree);

                break;
            }

            if (empty($array))
                continue;

            $result = $this->search_parent($name, $callback, $array);
            if (false !== $result)
            {
                $is_found = $result;
                break;
            }
        }

        if ($is_first_level)
            $this->updateStorage($subtree);

        return $is_found;
    }

    /**
     * @return array
     */
    protected function getStorage()
    {
        return $this->storage;
    }

    protected function getNodeKey(Node $node)
    {
        return $node->getName();
    }

    protected function updateStorage($array)
    {
        $this->storage = $array;
    }

}


class Node
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

abstract class Tree
{
    // создает узел (если $parentNode == NULL - корень)
    abstract protected function createNode(Node $node, $parentNode = NULL);

    // удаляет узел и все дочерние узлы
    abstract protected function deleteNode(Node $node);

    // один узел делает дочерним по отношению к другому
    abstract protected function attachNode(Node $node, Node $parent);

    // получает узел по названию
    abstract protected function getNode($nodeName);

    // преобразует дерево со всеми элементами в ассоциативный массив
    abstract protected function export();
}

$tree = new MyTree();

// Обеспечить выполнение следующего теста:

// 1. создать корень country
$tree->createNode(new Node('country'));

// 2. создать в нем узел kiev
$tree->createNode(new Node('kiev'), $tree->getNode('country'));

// 3. в узле kiev создать узел kremlin
$tree->createNode(new Node('kremlin'), $tree->getNode('kiev'));

// 4. в узле kremlin создать узел house
$tree->createNode(new Node('house'), $tree->getNode('kremlin'));

// 5. в узле kremlin создать узел tower
$tree->createNode(new Node('tower'), $tree->getNode('kremlin'));

// 4. в корневом узле создать узел moskow
$tree->createNode(new Node('moskow'), $tree->getNode('country'));

// 5. сделать узел kremlin дочерним узлом у moskow
$tree->attachNode($tree->getNode('kremlin'), $tree->getNode('moskow'));

// 6. в узле kiev создать узел maidan
$tree->createNode(new Node('maidan'), $tree->getNode('kiev'));

// 7. удалить узел kiev
$tree->deleteNode($tree->getNode('kiev'));

// 8. получить дерево в виде массива, сделать print_r
print('<pre>');
print_r($tree->export());
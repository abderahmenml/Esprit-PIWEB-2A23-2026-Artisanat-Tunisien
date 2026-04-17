<?php
class Idea
{
    private $id;
    private $title;
    private $budgetMin;
    private $status;
    private $category;
    private $description;
    private $dateCreation;

    public function __construct(
        $id,
        $title,
        $budgetMin,
        $status,
        $category,
        $description,
        $dateCreation
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->budgetMin = $budgetMin;
        $this->status = $status;
        $this->category = $category;
        $this->description = $description;
        $this->dateCreation = $dateCreation;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getBudgetMin()
    {
        return $this->budgetMin;
    }

    public function setBudgetMin($budgetMin)
    {
        $this->budgetMin = $budgetMin;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;
    }
}
?>

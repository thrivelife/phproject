<?php

namespace Controller;

class Issues extends Base {

	public function index($f3, $params) {
		$this->_requireLogin();

		$issues = new \DB\SQL\Mapper($f3->get("db.instance"), "issues_user_data");

		// Filter issue listing by URL parameters
		$filter = array();
		$args = $f3->get("GET");
		if(!empty($args["type"])) {
			$filter["type_id"] = intval($args["type"]);
		}
		if(isset($args["owner"])) {
			$filter["owner_id"] = intval($args["owner"]);
		}

		// Build SQL string to use for filtering
		$filter_str = "";
		foreach($filter as $i => $val) {
			$filter_str .= "$i = '$val' and ";
		}
		$filter_str = substr($filter_str, 0, strlen($filter_str) - 5); // Remove trailing "and "

		// Load type if a type_id was passed
		if(!empty($args["type"])) {
			$type = new \Model\Issue\Type();
			$type->load(array("id = ?", $args["type"]));
			if($type->id) {
				$f3->set("title", $type->name . "s");
				$f3->set("type", $type->cast());
			}
		}

		$f3->set("issues", $issues->paginate(0, 50, $filter_str));
		echo \Template::instance()->render("issues/index.html");
	}

	public function add($f3, $params) {
		$this->_requireLogin();

		if($f3->get("PARAMS.type")) {
			$type_id = $f3->get("PARAMS.type");
		} else {
			$type_id = 1;
		}

		$type = new \Model\Issue\Type();
		$type->load(array("id=?", $type_id));

		if(!$type->id) {
			$f3->error(500, "Issue type does not exist");
			return;
		}

		$users = new \Model\User();
		$f3->set("users", $users->paginate(0, 1000, null, array("order" => "name ASC")));

		$f3->set("title", "New " . $type->name);
		$f3->set("type", $type->cast());

		echo \Template::instance()->render("issues/edit.html");
	}

	public function edit($f3, $params) {
		$this->_requireLogin();

		$issue = new \Model\Issue();
		$issue->load(array("id=?", $f3->get("PARAMS.id")));

		if(!$issue->id) {
			$f3->error(404, "Issue does not exist");
			return;
		}

		$type = new \Model\Issue\Type();
		$type->load(array("id=?", $issue->type_id));

		$users = new \Model\User();
		$f3->set("users", $users->paginate(0, 1000, null, array("order" => "name ASC")));

		$f3->set("title", "Edit #" . $issue->id);
		$f3->set("issue", $issue->cast());
		$f3->set("type", $type->cast());

		echo \Template::instance()->render("issues/edit.html");
	}

	public function save($f3, $params) {
		$this->_requireLogin();

		if($f3->get("POST.name")) {
			$issue = new \Model\Issue();
			$issue->author_id = $f3->get("user.id");
			$issue->type_id = $f3->get("POST.type_id");
			$issue->created_date = date("Y-m-d H:i:s");
			$issue->name = $f3->get("POST.name");
			$issue->description = $f3->get("POST.description");
			$issue->owner_id = $f3->get("POST.owner_id");
			$issue->due_date = date("Y-m-d", strtotime($f3->get("POST.due_date")));
			$issue->parent_id = $f3->get("POST.parent_id");
			$issue->save();
			if($issue->id) {
				$f3->reroute("/issues/" . $issue->id);
			} else {
				$f3->error(500, "An error occurred saving the issue.");
			}
		}
	}

	public function single($f3, $params) {
		$this->_requireLogin();

		$issue = new \Model\Issue();
		$issue->load(array("id=?", $f3->get("PARAMS.id")));

		if(!$issue->id) {
			$f3->error(404);
			return;
		}

		$f3->set("title", $issue->name);

		$author = new \Model\User();
		$author->load(array("id=?", $issue->author_id));

		$f3->set("issue", $issue->cast());
		$f3->set("author", $author->cast());

		echo \Template::instance()->render("issues/single.html");
	}

}
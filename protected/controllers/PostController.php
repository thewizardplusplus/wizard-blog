<?php

class PostController extends CController {
	public function __construct($id, $module = NULL) {
		parent::__construct($id, $module);
		$this->defaultAction = 'list';
	}

	public function filters() {
		return array(
			'accessControl + control, create, update, delete',
			'postOnly + delete'
		);
	}

	public function accessRules() {
		return array(
			array(
				'allow',
				'actions' => array('list', 'view'),
				'users' => array('*')
			),
			array(
				'allow',
				'users' => array('admin')
			),
			array(
				'deny',
				'users' => array('*')
			)
		);
	}

	public function actionList() {
		$order = 'create';
		if (!empty($_GET['sort']) and $_GET['sort'] == 'modify') {
			$order = 'modify';
		}

		$criteria = new CDbCriteria(array('order' => $order . '_time DESC'));
		if (!empty($_GET['search'])) {
			$string = urldecode($_GET['search']);
			$strings = explode(' ', $string);
			$strings = array_map('trim', $strings);
			foreach ($strings as $string) {
				$criteria->addSearchCondition('title', $string, true, 'OR');
				$criteria->addSearchCondition('text', $string, true, 'OR');
			}
		}
		if (Yii::app()->user->isGuest) {
			$criteria->addCondition('published = 1');
		}

		$tags = array();
		if (!empty($_GET['tags'])) {
			$tags = array_map('trim', explode(',', $_GET['tags']));
		}
		foreach ($tags as $tag) {
			$criteria->addCondition(
				'FIND_IN_SET('
					. Yii::app()->db->quoteValue($tag)
					. ', `tags`)'
			);
		}

		$data_provider = new CActiveDataProvider(
			'Post',
			array(
				'criteria' => $criteria,
				'pagination' => array(
					'pagesize' => Parameters::get()->posts_on_page
				)
			)
		);

		$this->render(
			'list',
			array(
				'data_provider' => $data_provider,
				'order' => $order,
				'tags' => $tags
			)
		);
	}

	public function actionView($id) {
		$model = $this->loadModel($id);
		$this->render('view', array('model' => $model));
	}

	public function actionTagsAutocomplete() {
		if (empty($_GET['term'])) {
			echo '[]';
			return;
		}

		$tags = array();
		$posts = Post::model()->findAll('`tags` <> ""');
		if (!empty($posts)) {
			foreach ($posts as $post) {
				$tags = array_merge(
					$tags,
					array_map('trim', explode(',', $post->tags))
				);
			}

			$tags = array_unique($tags);
			sort($tags, SORT_STRING);
		} else {
			echo '[]';
			return;
		}

		$sample = str_replace('"', '\"', $_GET['term']);
		$last_comma_index = strrpos($sample, ',');
		if ($last_comma_index !== FALSE) {
			$prefix = trim(substr($sample, 0, $last_comma_index));

			$last_comma_index++;
			if ($last_comma_index < strlen($sample)) {
				$sample = trim(substr($sample, $last_comma_index));
			} else {
				$sample = '';
			}
		} else {
			$prefix = '';
		}

		$tags = array_filter(
			$tags,
			function($tag) use($sample) {
				return empty($sample) or strpos($tag, $sample) === 0;
			}
		);

		$tags = array_map(
			function($tag) use($prefix) {
				if (!empty($prefix)) {
					$value = $prefix . ', ' . $tag;
				} else {
					$value = $tag;
				}

				return "{ \"label\": \"$tag\", \"value\": \"$value\" }";
			},
			$tags
		);

		echo '[ ' . implode(', ', $tags) . ' ]';
	}

	public function actionTagList() {
		$tags = array();
		$number_of_posts = Post::model()->count('published = 1');
		if (!empty($number_of_posts)) {
			$posts = Post::model()->findAll('`tags` <> "" AND published = 1');
			if (!empty($posts)) {
				foreach ($posts as $post) {
					$tags = array_merge($tags, array_map('trim', explode(
						',', $post->tags)));
				}

				$tags = array_map(function($item) use ($number_of_posts) {
					return round(100 * $item / $number_of_posts);
				}, array_count_values($tags));
			}
		}

		if (!empty($tags)) {
			$tags_text = array_keys($tags);
			shuffle($tags_text);
			$shuffled_tags = array();
			foreach ($tags_text as $tag_text) {
				$shuffled_tags[$tag_text] = $tags[$tag_text];
			}
			$tags = $shuffled_tags;
		}

		echo json_encode($tags);
	}

	public function actionCreate() {
		$model = new Post;
		$this->performAjaxValidation($model);

		if (!isset($_POST['ajax']) and isset($_POST['Post'])) {
			$model->attributes = $_POST['Post'];
			$model->save();

			$this->redirect(array('view', 'id' => $model->id));
		}

		if (!isset($_POST['ajax'])) {
			$this->render('create', array('model' => $model));
		}
	}

	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$this->performAjaxValidation($model);

		if (isset($_POST['Post'])) {
			$model->attributes = $_POST['Post'];
			$result = $model->save();
			if (!isset($_POST['ajax']) and $result) {
				$this->redirect(array('view', 'id' => $id));
			}
		}

		if (!isset($_POST['ajax'])) {
			if (!empty($model->tags)) {
				$model->tags = str_replace(',', ', ', $model->tags);
			}
			$this->render('update', array('model' => $model));
		}
	}

	public function actionDelete($id) {
		$this->loadModel($id)->delete();

		if (!isset($_POST['ajax'])) {
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] :
				array('list'));
		}
	}

	private function loadModel($id) {
		$model = Post::model()->findByPk($id);
		if (is_null($model)) {
			throw new CHttpException(404, 'Запрашиваемая страница не найдена.');
		}

		return $model;
	}

	private function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'post-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}

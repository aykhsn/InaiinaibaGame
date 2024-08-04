<?php
/*
Template Name: いないいないばあ
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php Arkhe::root_attrs(); ?>>
<head>
<meta charset="utf-8">
<meta name="format-detection" content="telephone=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, viewport-fit=cover">
<?php
	wp_head();
	$setting = Arkhe::get_setting(); // SETTING取得
?>
<style>
    body {
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #bbbbea;
        overflow: hidden;
    }  
</style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/phaser@3/dist/phaser.min.js"></script>
    <script>
        const assetsUrl = "<?php echo get_template_directory_uri(); ?>/assets/games";

        const config = {
            type: Phaser.AUTO,
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: '#bbbbea', // 淡い紫色
            scale: {
                mode: Phaser.Scale.FIT,
                autoCenter: Phaser.Scale.CENTER_BOTH
            },
            parent: 'phaser-example',
            scene: {
                preload: preload,
                create: create,
                update: update
            }
        };

        const game = new Phaser.Game(config);

        const animalSize = game.config.height * 8 / 10 > game.config.width ? game.config.width : game.config.height / 2;

        let rabbit, bear, cat;
        let tapCounts = { rabbit: 0, bear: 0, cat: 0 };
        let currentAnimal = 'rabbit';
        let canTap = true;
        let animalHeight = animalSize;
        let animalWidth = (animalHeight * 7) / 10;
        let sparkles = [];
        let inaiinaiSound, baSound;

        function preload() {
            this.load.svg('rabbit_inai', `${assetsUrl}/rabbit_inai.svg`, { width: animalWidth, height: animalHeight });
            this.load.svg('rabbit_ba', `${assetsUrl}/rabbit_ba.svg`, { width: animalWidth, height: animalHeight });
            this.load.svg('bear_inai', `${assetsUrl}/bear_inai.svg`, { width: animalWidth, height: animalHeight });
            this.load.svg('bear_ba', `${assetsUrl}/bear_ba.svg`, { width: animalWidth, height: animalHeight });
            this.load.svg('cat_inai', `${assetsUrl}/cat_inai.svg`, { width: animalWidth, height: animalHeight });
            this.load.svg('cat_ba', `${assetsUrl}/cat_ba.svg`, { width: animalWidth, height: animalHeight });
            this.load.audio('inaiinai', `${assetsUrl}/inaiinai.mp3`);
            this.load.audio('ba', `${assetsUrl}/ba.mp3`);
            this.load.audio('shun', `${assetsUrl}/mokkin.mp3`);
        }

        function create() {
            rabbit = createAnimal(this, 'rabbit', 'rabbit_inai');
            bear = createAnimal(this, 'bear', 'bear_inai');
            cat = createAnimal(this, 'cat', 'cat_inai');

            rabbit.setScale(0);

            this.tweens.add({
                targets: rabbit,
                scaleX: 1,
                scaleY: 1,
                duration: 500,
                ease: 'Bounce.easeOut'
            });

            inaiinaiSound = this.sound.add('inaiinai');
            baSound = this.sound.add('ba');

            this.scale.on('resize', resize, this);
        }

        function update() {
            for (let sparkle of sparkles) {
                sparkle.alpha -= 0.01;
                if (sparkle.alpha <= 0) {
                    sparkle.destroy();
                    sparkles.splice(sparkles.indexOf(sparkle), 1);
                }
            }
        }

        function resize(gameSize) {
            animalHeight = gameSize.height / 2;
            animalWidth = (animalHeight * 7) / 10;
            resizeAnimal(rabbit);
            resizeAnimal(bear);
            resizeAnimal(cat);
        }

        function createAnimal(scene, name, texture) {
            const animal = scene.add.sprite(scene.cameras.main.centerX, scene.cameras.main.centerY, texture)
                .setInteractive()
                .setDisplaySize(animalWidth, animalHeight)
                .setDepth(1)
                .setVisible(name === 'rabbit');

            animal.on('pointerdown', () => {
                if (canTap) {
                    handleAnimalClick(name, animal, scene);
                }
            });

            return animal;
        }

        function resizeAnimal(animal) {
            animal.setDisplaySize(animalWidth, animalHeight);
            animal.setPosition(animal.scene.cameras.main.centerX, animal.scene.cameras.main.centerY);
        }

        function handleAnimalClick(name, animal, scene) {
            tapCounts[name]++;

            if (tapCounts[name] === 1) {
                canTap = false;
                inaiinaiSound.play();
                inaiinaiSound.once('complete', () => {
                    canTap = true;
                });
            } else if (tapCounts[name] === 2) {
                canTap = false;
                animal.setTexture(name + '_ba');
                baSound.play();
                addSparkles(scene, animal);
                baSound.once('complete', () => {
                    setTimeout(() => {
                        switchToNextAnimal(name, scene);
                    }, 1400);
                });
            }
        }

        function switchToNextAnimal(current, scene) {
            const shunSound = scene.sound.add('shun');

            if (current === 'rabbit') {
                animateSwitch(rabbit, bear, scene, shunSound, 0xfbeda5);
                currentAnimal = 'bear';
            } else if (current === 'bear') {
                bear.setTexture('bear_inai'); // テクスチャをリセット
                animateSwitch(bear, cat, scene, shunSound, 0xffc4c4);
                currentAnimal = 'cat';
            } else if (current === 'cat') {
                cat.setTexture('cat_inai'); // テクスチャをリセット
                animateSwitch(cat, rabbit, scene, shunSound, 0xbbbbea); // 淡い紫色
                rabbit.setTexture('rabbit_inai');
                currentAnimal = 'rabbit';
            }
            tapCounts[current] = 0; // タップカウントをリセット
        }

        function animateSwitch(currentAnimal, nextAnimal, scene, sound, newBackgroundColor) {
            scene.tweens.add({
                targets: currentAnimal,
                scaleX: 0,
                scaleY: 0,
                duration: 200,
                ease: 'Power2',
                onComplete: () => {
                    currentAnimal.setVisible(false);
                    sound.play();
                    nextAnimal.setScale(0); // 次の動物のスケールをリセット
                    nextAnimal.setVisible(true);
                    scene.tweens.add({
                        targets: nextAnimal,
                        scaleX: 1,
                        scaleY: 1,
                        duration: 500,
                        ease: 'Bounce.easeOut',
                        onComplete: () => {
                            canTap = true;
                        }
                    });
                    scene.cameras.main.setBackgroundColor(newBackgroundColor);
                    document.body.style.backgroundColor = Phaser.Display.Color.IntegerToColor(newBackgroundColor).rgba;
                }
            });
        }

        function addSparkles(scene, animal) {
            const pastelColors = [
                0xffc0cb, // Pink
                0xffb6c1, // LightPink
                0xffd700, // Gold
                0xffe4e1, // MistyRose
                0xdda0dd, // Plum
                0xbbbbea, // Lavender
                0xb0e0e6, // PowderBlue
                0xadd8e6, // LightBlue
                0x98fb98, // PaleGreen
                0xf5deb3, // Wheat
            ];

            for (let i = 0; i < 30; i++) {
                let sparkle = scene.add.star(
                    Phaser.Math.Between(animal.x - animal.displayWidth / 2, animal.x + animal.displayWidth / 2),
                    Phaser.Math.Between(animal.y - animal.displayHeight / 2, animal.y + animal.displayHeight / 2),
                    5,
                    Phaser.Math.Between(6, 10),
                    Phaser.Math.Between(10, 14),
                    Phaser.Utils.Array.GetRandom(pastelColors)
                );
                sparkle.setDepth(0);
                sparkle.alpha = 1;
                sparkles.push(sparkle);
            }
        }

        window.addEventListener('resize', () => {
            game.scale.resize(window.innerWidth, window.innerHeight);

            resizeAnimals();
        });

        function resizeAnimals() {
            const animalSize = game.config.height * 8 / 10 > game.config.width ? game.config.width : game.config.height / 2;

            let animalHeight = animalSize;
            let animalWidth = (animalHeight * 7) / 10;
            
            animal.setDisplaySize(animalHeight, animalWidth);
        }
    </script>
</body>
</html>
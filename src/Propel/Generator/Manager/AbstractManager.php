<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;
use Propel\Runtime\Map\DatabaseMap;

/**
 * An abstract base Propel manager to perform work related to the schema
 * file.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@zenplex.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
abstract class AbstractManager
{
    /**
     * Data models that we collect. One from each schema file.
     */
    protected $dataModels = [];

    /**
     * @var Database[]
     */
    protected $databases;

    /**
     * Map of data model name to database name.
     * Should probably stick to the convention
     * of them being the same but I know right now
     * in a lot of cases they won't be.
     */
    protected $dataModelDbMap;

    /**
     * DB encoding to use for SchemaReader object
     */
    protected $dbEncoding = 'UTF-8';

    /**
     * Gets list of all used schemas
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var \Closure
     */
    private $loggerClosure = null;

    /**
     * Have datamodels been initialized?
     * @var boolean
     */
    private $dataModelsLoaded = false;

    /**
     * An initialized GeneratorConfig object.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * Returns the list of schemas.
     *
     * @return array
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Sets the schemas list.
     *
     * @param array
     */
    public function setSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
    }

    /**
     * Sets the working directory path.
     *
     * @param string $workingDirectory
     */
    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * Returns the working directory path.
     *
     * @return string
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }


    /**
     * Returns the data models that have been
     * processed.
     *
     * @return Schema[]
     */
    public function getDataModels(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModels;
    }

    /**
     * Returns the data model to database name map.
     *
     * @return array
     */
    public function getDataModelDbMap(): array
    {
        if (!$this->dataModelsLoaded) {
            $this->loadDataModels();
        }

        return $this->dataModelDbMap;
    }

    /**
     * @return Database[]
     */
    public function getDatabases(): array
    {
        if (null === $this->databases) {
            /** @var DatabaseMap[] $databases */
            $databases = [];
            foreach ($this->getDataModels() as $dataModel) {
                foreach ($dataModel->getDatabases() as $database) {
                    if (!isset($databases[$database->getName()])) {
                        $databases[$database->getName()] = $database;
                    } else {
                        $entities = $database->getEntities();
                        // Merge entities from different schema.xml to the same database
                        foreach ($entities as $entity) {
                            if (!$databases[$database->getName()]->hasEntity($entity->getName(), true)) {
                                $databases[$database->getName()]->addEntity($entity);
                            }
                        }
                    }
                }
            }
            $this->databases = $databases;
        }

        return $this->databases;
    }

    /**
     * @param  string $name
     * @return Database|null
     */
    public function getDatabase($name):? Database
    {
        $dbs = $this->getDatabases();
        return @$dbs[$name];
    }

    /**
     * Sets the current target database encoding.
     *
     * @param string $encoding Target database encoding
     */
    public function setDbEncoding(string $encoding): void
    {
        $this->dbEncoding = $encoding;
    }

    /**
     * Sets a logger closure.
     *
     * @param \Closure $logger
     */
    public function setLoggerClosure(\Closure $logger): void
    {
        $this->loggerClosure = $logger;
    }

    /**
     * Returns all matching XML schema files and loads them into data models for
     * class.
     */
    protected function loadDataModels()
    {
        $schemas = [];
        $totalNbEntities   = 0;
        $dataModelFiles  = $this->getSchemas();

        if (empty($dataModelFiles)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }
//Move This to the XMLLoader
        // Make a transaction for each file
        foreach ($dataModelFiles as $schema) {
            $dmFilename = $schema->getPathName();
            $this->log('Processing: ' . $schema->getFileName());

            //@todo load datamodel

            $this->includeExternalSchemas($dom, $schema->getPath());

            $xmlParser = new SchemaReader($this->dbEncoding);
            $xmlParser->setGeneratorConfig($this->getGeneratorConfig());
            $schema = $xmlParser->parseString($dom->saveXML(), $dmFilename);
            $nbEntities = $schema->getDatabase(null, false)->countEntities();
            $totalNbEntities += $nbEntities;

            $this->log(sprintf('  %d entities processed successfully', $nbEntities));

            $schema->setName($dmFilename);
            $schemas[] = $schema;
        }

        $this->log(sprintf('%d entities found in %d schema files.', $totalNbEntities, count($dataModelFiles)));

        if (empty($schemas)) {
            throw new BuildException('No schema files were found (matching your schema fileset definition).');
        }

        foreach ($schemas as $schema) {
            // map schema filename with database name
            $this->dataModelDbMap[$schema->getName()] = $schema->getDatabase(null, false)->getName();
        }

        if (count($schemas) > 1 && $this->getGeneratorConfig()->get()['generator']['packageObjectModel']) {
            $schema = $this->joinDataModels($schemas);
            $this->dataModels = [$schema];
        } else {
            $this->dataModels = $schemas;
        }

        foreach ($this->dataModels as $schema) {
            $schema->doFinalInitialization();
        }

        $this->dataModelsLoaded = true;
    }

    /**
     * Replaces all external-schema nodes with the content of xml schema that node refers to
     *
     * Recurses to include any external schema referenced from in an included xml (and deeper)
     * Note: this function very much assumes at least a reasonable XML schema, maybe it'll proof
     * users don't have those and adding some more informative exceptions would be better
     *
     * @param \DOMDocument $dom
     * @param string       $srcDir
     */
    //@todo move to schemaLoaders
    protected function includeExternalSchemas(\DOMDocument $dom, $srcDir)
    {
        $databaseNode = $dom->getElementsByTagName('database')->item(0);
        $externalSchemaNodes = $dom->getElementsByTagName('external-schema');

        $nbIncludedSchemas = 0;
        while ($externalSchema = $externalSchemaNodes->item(0)) {
            $include = $externalSchema->getAttribute('filename');
            $referenceOnly = $externalSchema->getAttribute('referenceOnly');
            $this->log('Processing external schema: ' . $include);

            $externalSchema->parentNode->removeChild($externalSchema);

            $externalSchemaDom = new \DOMDocument('1.0', 'UTF-8');
            $externalSchemaDom->load(realpath($include));

            // The external schema may have external schemas of its own ; recurs
            $this->includeExternalSchemas($externalSchemaDom, $srcDir);
            foreach ($externalSchemaDom->getElementsByTagName('entity') as $entityNode) {
                if ($referenceOnly) {
                    $entityNode->setAttribute("skipSql", "true");
                }
                $databaseNode->appendChild($dom->importNode($entityNode, true));
            }

            $nbIncludedSchemas++;
        }

        return $nbIncludedSchemas;
    }

    /**
     * Joins the datamodels collected from schema.xml files into one big datamodel.
     * We need to join the datamodels in this case to allow for foreign keys
     * that point to entities in different packages.
     *
     * @param  array  $schemas
     * @return Schema
     */
    protected function joinDataModels(array $schemas)
    {
        $mainSchema = array_shift($schemas);
        $mainSchema->joinSchemas($schemas);

        return $mainSchema;
    }

    /**
     * Returns the GeneratorConfig object for this manager or creates it
     * on-demand.
     *
     * @return GeneratorConfigInterface
     */
    protected function getGeneratorConfig(): GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the GeneratorConfigInterface implementation.
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
    {
        $this->generatorConfig = $generatorConfig;
    }

    protected function log($message)
    {
        if (null !== $this->loggerClosure) {
            $closure = $this->loggerClosure;
            $closure($message);
        }
    }
}

<?php

namespace Command\EC2CnameCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sets a CNAME record pointing to an EC2 instance based on a tag value
 *
 * This command requires the cli53 tool to be installed
 * see https://cantina.co/automated-dns-for-aws-instances-using-route-53/
 * Download from https://github.com/barnybug/cli53/releases/latest
 */
class EC2CnameCommand extends Command
{
    private $conf;

    public function __construct()
    {
        $this->conf = parse_ini_file("config.ini");
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        // @TODO Enable optional arguments to override config
        $this
          ->setName('aws-tools:ec2-cname')
          ->setDescription(
            'Set a cname record pointing to an EC2 instances public hostname'
          )
          ->addArgument(
            'Subdomain tag value',
            InputArgument::REQUIRED,
            'The value of the subdomain tag that will target the instance id and provide the subdomain part'
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = $this->conf;
        $zone = $conf['zone'];
        $ttl = $conf['ttl'];

        $tagValue = $input->getArgument('Subdomain tag value');

        try {
            $publicDnsName = $this->RetrievePublicDNSName($tagValue);
        }
        catch(\Exception $e) {
            $output->writeln("An error occurred");
            $output->writeln("<error>{$e->getMessage()}</error>");
            $output->writeln("aborting");
            return;
        }

        // Create the CNAME record
        $command = "cli53 rc $zone '$tagValue $ttl CNAME $publicDnsName' --replace";
        $output->writeln("<info>Running:</info>$command");
        exec($command, $cmdOutput, $returnVar);
        foreach ($cmdOutput as $cmd_output_line) {
            $output->writeln("<info>$cmd_output_line</info>");
        }
    }

    /**
     * Retrieves the public DNS name for an EC2 instance based on a tag value
     *
     * @param $tagValue Used to identify the relevant EC2 instance
     * @return string The public domain name
     * @throws \Exception
     */
    protected function RetrievePublicDNSName($tagValue)
    {
        $conf = $this->conf;

        $filters = "Name=tag:{$conf['subdomain_tag']},Values={$tagValue}";

        $command = "aws ec2 describe-instances --filters \"{$filters}\" --query Reservations[0].Instances[0].PublicDnsName";
        exec($command, $cmdOutput, $returnVar);

        if ($returnVar !== 0) {
            $exceptionMessage = implode(PHP_EOL, $cmdOutput);
            throw new \Exception($exceptionMessage);
        }

        // trim the surrounding quotes
        $dnsName = trim($cmdOutput[0], '"');
        // add a final . to the end of the DNS name otherwise the zone gets appended to it
        $dnsName .= '.';

        return $dnsName;
    }
}
